<?php
session_start();
include "koneksi.php";

// Pastikan konselor login (opsional tapi lebih aman)
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'konselor') {
    header("Location: login.php");
    exit;
}

// 1. Ambil ID dari URL
$id_asesmen = $_GET['id_asesmen'] ?? null;
if (!$id_asesmen) {
    echo "<script>alert('ID Asesmen tidak ditemukan!'); window.location='konselor_asesmen.php';</script>";
    exit;
}
$id_asesmen = (int)$id_asesmen;

// 2. Query Data Lengkap Mahasiswa & Asesmen (pakai LEFT JOIN biar aman)
$query = "SELECT a.*,
                 m.nama AS nama_mhs,
                 m.nim,
                 m.no_hp,
                 m.email,
                 j.nama_jurusan,
                 f.nama_fakultas
          FROM asesmen a
          LEFT JOIN mahasiswa m ON a.nim = m.nim
          LEFT JOIN jurusan j ON j.id_jurusan = m.id_jurusan
          LEFT JOIN fakultas f ON f.id_fakultas = j.id_fakultas
          WHERE a.id_asesmen = $id_asesmen";

$result = mysqli_query($koneksi, $query);
if (!$result) die("Query Error: " . mysqli_error($koneksi));

$data = mysqli_fetch_assoc($result);
if (!$data) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Data tidak ditemukan.</div></div>";
    exit;
}

// 3. Ambil Data Rekomendasi Karier (LEFT JOIN biar aman kalau profil_pekerjaan terhapus)
$query_rek = "SELECT hr.*, pp.nama_pekerjaan
              FROM hasil_rekomendasi hr
              LEFT JOIN profil_pekerjaan pp ON hr.id_pekerjaan = pp.id_pekerjaan
              WHERE hr.id_asesmen = $id_asesmen
              ORDER BY hr.urutan_rekomendasi ASC";

$res_rek = mysqli_query($koneksi, $query_rek);
$rekomendasi = [];
if ($res_rek) {
    while ($row_rek = mysqli_fetch_assoc($res_rek)) $rekomendasi[] = $row_rek;
}

// 4. Ambil SEMUA sesi konseling untuk asesmen ini
$q_konseling = mysqli_query(
    $koneksi,
    "SELECT id_konseling, id_user, jadwal_konseling, status, sesi, catatan
     FROM konseling
     WHERE id_asesmen = $id_asesmen
     ORDER BY sesi ASC, id_konseling ASC"
);
if (!$q_konseling) die("Query konseling error: " . mysqli_error($koneksi));

$konseling_list = [];
while ($r = mysqli_fetch_assoc($q_konseling)) $konseling_list[] = $r;

// Helper: badge status
function badgeStatus($status) {
    $status = $status ?? '-';
    $cls = 'bg-secondary';
    if ($status === 'Disetujui') $cls = 'bg-info text-dark';
    if ($status === 'Lanjutan')  $cls = 'bg-warning text-dark';
    if ($status === 'Selesai')   $cls = 'bg-success';
    return [$cls, $status];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Asesmen - <?= htmlspecialchars($data['nama_mhs'] ?? 'Detail') ?></title>

    <link rel="stylesheet" href="style_detail.css">
    <link rel="icon" type="image/png" href="../foto/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Fix cepat kalau style_detail.css menimpa grid -->
    <style>
      .row { display:flex !important; flex-wrap:wrap !important; }
    </style>
</head>
<body>

<div id="content">
    <div class="container mt-4 mb-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">Detail Asesmen Konseling</h4>
                <small class="text-muted">
                    Tanggal Asesmen: <?= !empty($data['tanggal_asesmen']) ? date('d F Y', strtotime($data['tanggal_asesmen'])) : '-' ?>
                </small>
            </div>
            <div class="no-print">
                <a href="konselor_asesmen.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="bi bi-printer"></i> Cetak
                </button>
            </div>
        </div>

        <div class="row">
            <!-- KIRI -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm card-profile h-100">
                    <div class="card-body text-center">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($data['nama_mhs'] ?? 'User') ?>&background=random"
                             class="rounded-circle mb-3" width="100" alt="Foto">

                        <h5><?= htmlspecialchars($data['nama_mhs'] ?? '-') ?></h5>
                        <p class="text-muted mb-1">NIM: <?= htmlspecialchars($data['nim'] ?? '-') ?></p>
                        <span class="badge bg-primary mb-1"><?= htmlspecialchars($data['nama_jurusan'] ?? 'Jurusan -') ?></span><br>
                        <small class="text-muted"><?= htmlspecialchars($data['nama_fakultas'] ?? '') ?></small>
                        <hr>

                        <div class="text-start">
                            <small class="text-muted d-block">Kontak WhatsApp:</small>
                            <?php if(!empty($data['no_hp'])): ?>
                                <a href="https://wa.me/<?= '62'.ltrim($data['no_hp'], '0') ?>"
                                   target="_blank" class="btn btn-success btn-sm w-100 mt-1">
                                    <i class="bi bi-whatsapp"></i> Hubungi <?= htmlspecialchars($data['no_hp']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-danger small">No HP tidak tersedia</span>
                            <?php endif; ?>
                        </div>

                        <div class="text-start">
                            <small class="text-muted d-block">Email Pengguna:</small>
                            <?php if(!empty($data['email'])): ?>
                                <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= urlencode($data['email']) ?>"
                                target="_blank"
                                class="btn btn-success btn-sm w-100 mt-1">
                                    <i class="bi bi-envelope"></i> Kirim Email via Gmail
                                </a>
                            <?php else: ?>
                                <span class="text-danger small">Email Tidak Tersedia</span>
                            <?php endif; ?>
                        </div>

                        <!-- Jadwal Terkait (tanpa catatan panjang) -->
                        <div class="mt-4 text-start">
                            <h6 class="text-muted border-bottom pb-2">Jadwal Terkait</h6>

                            <?php if (!empty($konseling_list)): ?>
                                <div class="small">
                                    <?php foreach ($konseling_list as $ks): ?>
                                        <?php
                                            $sesi = (int)($ks['sesi'] ?? 1);
                                            $jadwalRaw = $ks['jadwal_konseling'] ?? '';
                                            $tgl = '-'; $jam = '-';
                                            if (!empty($jadwalRaw) && $jadwalRaw !== '0000-00-00 00:00:00') {
                                                $tgl = date('d-m-Y', strtotime($jadwalRaw));
                                                $jam = date('H:i', strtotime($jadwalRaw));
                                            }
                                            [$badgeClass, $statusTxt] = badgeStatus($ks['status'] ?? '-');
                                        ?>

                                        <div class="border rounded p-2 mb-2 bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong>Sesi <?= $sesi ?></strong>
                                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusTxt) ?></span>
                                            </div>
                                            <div class="mt-1">
                                                <div><strong>Tanggal:</strong> <?= htmlspecialchars($tgl) ?></div>
                                                <div><strong>Jam:</strong> <?= htmlspecialchars($jam) ?> WIB</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary small mb-0">
                                    Belum ada jadwal konseling.
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

            <!-- KANAN -->
            <div class="col-lg-8">
                <!-- Hasil Asesmen -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 text-primary">Hasil Asesmen</h5>
                    </div>
                    <div class="card-body">

                        <div class="mb-4">
                            <p class="label-tanya fw-bold">1. Permasalahan:</p>
                            <ul class="list-group">
                                <div class="text-jawaban mb-2 p-2 bg-light rounded">
                                    <?= nl2br(htmlspecialchars($data['permasalahan'] ?? '-')) ?>
                                </div>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Kategori Permasalahan 
                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($data['kategori_permasalahan'] ?? '-') ?></span>
                                </li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <p class="label-tanya fw-bold">2. Kesimpulan Analisis:</p>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Hasil Asesmen (COI Dominan)
                                    <span class="fw-bold text-primary"><?= strtoupper($data['hasil_asesmen'] ?? '-') ?></span>
                                </li>

                                <li class="list-group-item">
                                    <div class="mb-2 fw-bold text-dark">Rekomendasi Karier (Top 3):</div>

                                    <?php if (!empty($rekomendasi)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Nama Pekerjaan</th>
                                                        <th class="text-center" width="20%">Skor Kecocokan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($rekomendasi as $rek): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($rek['nama_pekerjaan'] ?? 'Nama Pekerjaan Terhapus') ?></td>
                                                            <td class="text-center">
                                                                <span class="badge bg-success">
                                                                    <?= number_format((float)($rek['hasil_skor'] ?? 0), 2) ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning py-2 mb-0 small">
                                            <i class="bi bi-exclamation-circle"></i> Data rekomendasi belum tersedia atau belum dihitung.
                                        </div>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <p class="label-tanya fw-bold">3. Rincian Skor COI:</p>
                            <div class="row g-2">
                                <?php
                                $skor_list = [
                                    'Autonomy' => $data['skor_autonomy'] ?? 0,
                                    'Security' => $data['skor_security'] ?? 0,
                                    'Technical' => $data['skor_tf'] ?? 0,
                                    'GM' => $data['skor_gm'] ?? 0,
                                    'Entrepreneur' => $data['skor_ec'] ?? 0,
                                    'Service' => $data['skor_service'] ?? 0,
                                    'Pure Challenge' => $data['skor_challenge'] ?? 0,
                                    'Lifestyle' => $data['skor_lifestyle'] ?? 0,
                                ];

                                foreach ($skor_list as $label => $nilai):
                                ?>
                                    <div class="col-6 col-md-3">
                                        <div class="p-2 border rounded text-center bg-light">
                                            <small class="d-block text-muted mb-1"><?= htmlspecialchars($label) ?></small>
                                            <span class="fw-bold fs-5 text-primary"><?= htmlspecialchars((string)$nilai) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Catatan Konseling (dipisah biar rapi) -->
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 text-primary">Catatan Konseling</h5>
                    </div>
                    <div class="card-body">

                        <?php if (!empty($konseling_list)): ?>
                            <?php foreach ($konseling_list as $ks): ?>
                                <?php
                                    $sesi = (int)($ks['sesi'] ?? 1);
                                    $status = $ks['status'] ?? '-';
                                    $catatan = trim($ks['catatan'] ?? '');

                                    $jadwalRaw = $ks['jadwal_konseling'] ?? '';
                                    $tglJam = '-';
                                    if (!empty($jadwalRaw) && $jadwalRaw !== '0000-00-00 00:00:00') {
                                        $tglJam = date('d-m-Y H:i', strtotime($jadwalRaw)) . ' WIB';
                                    }

                                    [$badgeClass, $statusTxt] = badgeStatus($status);
                                ?>

                                <div class="border rounded p-3 mb-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Sesi <?= $sesi ?></strong>
                                            <div class="text-muted small"><?= htmlspecialchars($tglJam) ?></div>
                                        </div>
                                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusTxt) ?></span>
                                    </div>

                                    <hr class="my-2">

                                    <?php if ($catatan !== ''): ?>
                                        <div class="bg-white p-2 border rounded fst-italic text-dark">
                                            <?= nl2br(htmlspecialchars($catatan)) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted fst-italic small">Catatan belum diisi.</div>
                                    <?php endif; ?>
                                </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-secondary small mb-0">
                                Belum ada sesi konseling.
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
