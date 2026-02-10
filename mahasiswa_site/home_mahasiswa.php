<?php
session_start();
include "koneksi.php";

// 1. Cek Login
if (!isset($_SESSION['nim'])) {
    header("Location: login_mahasiswa.php");
    exit;
}

$nim = $_SESSION['nim'];

// 2. Ambil Data Profil
$query_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE nim = '$nim'");
if (!$query_mhs) {
    die("Error Data Mahasiswa: " . mysqli_error($koneksi));
}
$mhs = mysqli_fetch_assoc($query_mhs);


// ========================================================
// PERBAIKAN: TAMBAHKAN PENGECEKAN ERROR DI SINI
// ========================================================

// 3. Ambil Riwayat Konseling (JOIN TABLE)
// Kita gabungkan tabel 'konseling' (k) dengan 'asesmen' (a)
// Pastikan nama tabel asesmen sesuai database Anda (misal: 'asesmen' atau 'hasil_asesmen'?)
$sql_konseling = "SELECT k.*, a.tanggal_asesmen 
                  FROM konseling k 
                  JOIN asesmen a ON k.id_asesmen = a.id_asesmen 
                  WHERE a.nim = '$nim' 
                  ORDER BY k.id_konseling DESC";

$query_konseling = mysqli_query($koneksi, $sql_konseling);

// Cek error query biar tidak blank jika salah nama tabel
if (!$query_konseling) {
    die("Error Query Konseling: " . mysqli_error($koneksi));
}


// 4. Ambil Status Konseling Terakhir (Juga perlu JOIN)
// Ini untuk widget statistik "Status Konseling Terakhir"
$sql_last_status = "SELECT k.status 
                    FROM konseling k 
                    JOIN asesmen a ON k.id_asesmen = a.id_asesmen 
                    WHERE a.nim = '$nim' 
                    ORDER BY k.id_konseling DESC LIMIT 1";

$query_last = mysqli_query($koneksi, $sql_last_status);
$last_data  = mysqli_fetch_assoc($query_last);
$status_text = $last_data['status'] ?? 'Belum ada';


// DEFAULT JURUSAN
$nama_jurusan = '-'; 

// JIKA MAHASISWA PUNYA ID_JURUSAN, CARI NAMANYA DI TABEL JURUSAN
if (!empty($mhs['id_jurusan'])) {
    $id_jur = $mhs['id_jurusan'];
    
    // Query ambil nama jurusan
    $sql_jurusan = "SELECT nama_jurusan FROM jurusan WHERE id_jurusan = '$id_jur'";
    $query_jurusan = mysqli_query($koneksi, $sql_jurusan);
    
    if ($query_jurusan && mysqli_num_rows($query_jurusan) > 0) {
        $data_jurusan = mysqli_fetch_assoc($query_jurusan);
        $nama_jurusan = $data_jurusan['nama_jurusan'];
    }
}

// 5. Ambil Riwayat Asesmen
// (Asumsi tabel ini langsung punya NIM, kalau tidak punya NIM, logic-nya harus diubah juga)
$sql_asesmen = "SELECT * FROM asesmen WHERE nim = '$nim' ORDER BY id_asesmen DESC";
$query_asesmen = mysqli_query($koneksi, $sql_asesmen);

// Pengecekan Error (Opsional, untuk jaga-jaga)
if (!$query_asesmen) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Mahasiswa - CDC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f4f6f9; }
        .card-dashboard { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .btn-action { padding: 15px; border-radius: 10px; font-weight: bold; transition: 0.3s; }
        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .status-badge { font-size: 0.85em; padding: 5px 10px; border-radius: 20px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">CDC Mahasiswa</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3">Halo, <?php echo htmlspecialchars($mhs['nama']); ?></span>
            <a href="login_mahasiswa.php" class="btn btn-sm btn-light text-primary fw-bold">Keluar</a>
        </div>
    </div>
</nav>

<div class="container">
    
    <div class="row">
        <div class="col-lg-4">
            
            <div class="card card-dashboard text-center p-3">
                <div class="card-body">
                    <?php
                        $foto_profil = "https://ui-avatars.com/api/?name=" . urlencode($mhs['nama']) . "&background=random";
                        if (!empty($mhs['foto']) && file_exists("uploads/" . $mhs['foto'])) {
                            $foto_profil = "uploads/" . $mhs['foto'];
                        }
                    ?>

                    <img src="<?php echo $foto_profil; ?>" 
                        class="rounded-circle mb-3 shadow-sm" 
                        width="80" 
                        height="80" 
                        style="object-fit: cover;">
                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($mhs['nama']); ?></h5>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($mhs['nim']); ?></p>
                    
                    <ul class="list-group list-group-flush text-start mb-3">
                        <li class="list-group-item">
                            <i class="fas fa-envelope me-2 text-primary"></i> 
                            <?php echo htmlspecialchars($mhs['email']); ?>
                        </li>
                        
                        <li class="list-group-item">
                            <i class="fas fa-phone me-2 text-success"></i> 
                            <?php echo htmlspecialchars($mhs['no_hp'] ?? '-'); ?>
                        </li>
                        
                        <li class="list-group-item">
                            <i class="fas fa-graduation-cap me-2 text-warning"></i> 
                            <?php echo htmlspecialchars($nama_jurusan); ?>
                        </li>

                        <li class="list-group-item">
                            <i class="fas fa-venus-mars me-2 text-info"></i> 
                            <?php echo htmlspecialchars($mhs['jenis_kelamin']); ?>
                        </li>

                        <li class="list-group-item">
                            <i class="fas fa-calendar-alt me-2 text-danger"></i> 
                            Angkatan <?php echo htmlspecialchars($mhs['angkatan']); ?>
                        </li>
                    </ul>

                    <a href="edit_profil.php" class="btn btn-outline-primary w-100">
                        <i class="fas fa-edit me-1"></i> Edit Profil
                    </a>
                </div>
            </div>

            <div class="d-grid gap-3">
                <a href="asesmen2.php" class="btn btn-primary btn-action text-start">
                    <i class="fas fa-clipboard-check fa-2x float-end opacity-25"></i>
                    <div class="fs-5">Mulai Asesmen</div>
                    <small>Cek potensi karirmu sekarang</small>
                </a>

            </div>

        </div>

        <div class="col-lg-8">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card card-dashboard bg-white border-start border-4 border-primary">
                        <div class="card-body">
                            <h6 class="text-muted">Total Asesmen Selesai</h6>
                            <h2><?php echo mysqli_num_rows($query_asesmen); ?> <small class="fs-6 text-muted">kali</small></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-dashboard bg-white border-start border-4 border-success">
                        <div class="card-body">
                            <h6 class="text-muted">Status Konseling Terakhir</h6>
                            <h4 class="text-success"><?php echo ucfirst($status_text); ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-dashboard">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="konseling-tab" data-bs-toggle="tab" data-bs-target="#konseling" type="button">Riwayat Konseling</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="asesmen-tab" data-bs-toggle="tab" data-bs-target="#asesmen" type="button">Riwayat Asesmen</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="myTabContent">
                        
                        <div class="tab-pane fade show active" id="konseling" role="tabpanel">
                            <?php if (mysqli_num_rows($query_konseling) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tanggal Pengajuan</th>
                                                <th>Topik</th>
                                                <th>Jadwal</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($query_konseling as $row): ?>
                                            <tr>
                                                <td><?php echo date('d M Y', strtotime($row['tanggal_asesmen'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['catatan']); ?></td>
                                                <td>
                                                    <?php echo ($row['jadwal_konseling']) ? date('d M Y H:i', strtotime($row['jadwal_konseling'])) : '-'; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $st = strtolower($row['status']);
                                                    $badge = 'bg-secondary';
                                                    if ($st == 'menunggu') $badge = 'bg-warning text-dark';
                                                    if ($st == 'terjadwal') $badge = 'bg-info text-dark';
                                                    if ($st == 'selesai') $badge = 'bg-success';
                                                    if ($st == 'ditolak') $badge = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?php echo $badge; ?>"><?php echo ucfirst($row['status']); ?></span>
                                                </td>
                                                <td>
                                                    <a href="detail_mahasiswa.php?id=<?php echo $row['id_asesmen']; ?>" class="btn btn-sm btn-outline-primary">Lihat</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">Belum ada riwayat konseling.</div>
                            <?php endif; ?>
                        </div>

                        <div class="tab-pane fade" id="asesmen" role="tabpanel">
                             <?php if (mysqli_num_rows($query_asesmen) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Skor/Hasil</th>
                                                <th>Kesimpulan</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($query_asesmen as $row): ?>
                                            <tr>
                                                <td><?php echo date('d M Y', strtotime($row['tanggal_asesmen'])); ?></td>
                                                <td><?php 
                                                    // 1. Ambil kata kunci hasil (bersihkan spasi & huruf kecil biar aman)
                                                    $hasil = strtolower(trim($row['hasil_asesmen']));
                                                    $nilai_skor = 0; // Default nilai 0

                                                    // 2. Cek hasil asesmennya apa, lalu ambil skor dari kolom yang sesuai
                                                    switch ($hasil) {
                                                        case 'autonomy':
                                                            $nilai_skor = $row['skor_autonomy'];
                                                            break;
                                                        case 'security':
                                                            $nilai_skor = $row['skor_security'];
                                                            break;
                                                        case 'tf': // Technical Functional
                                                            $nilai_skor = $row['skor_tf'];
                                                            break;
                                                        case 'gm': // General Management
                                                            $nilai_skor = $row['skor_gm'];
                                                            break;
                                                        case 'ec': // Entrepreneurial Creativity
                                                            $nilai_skor = $row['skor_ec'];
                                                            break;
                                                        case 'service':
                                                            $nilai_skor = $row['skor_service'];
                                                            break;
                                                        case 'challenge':
                                                            $nilai_skor = $row['skor_challenge'];
                                                            break;
                                                        case 'lifestyle':
                                                            $nilai_skor = $row['skor_lifestyle'];
                                                            break;
                                                        default:
                                                            $nilai_skor = 0; // Jika tidak ada yang cocok
                                                    }

                                                    // 3. Tampilkan Skor
                                                    echo htmlspecialchars($nilai_skor); 
                                                    ?>
                                                    </td>
                                                <td><?php echo htmlspecialchars(substr($row['hasil_asesmen'], 0, 50)) . '...'; ?></td>
                                                <td>
                                                    <a href="detail_mahasiswa.php?id=<?php echo $row['id_asesmen']; ?>" class="btn btn-sm btn-outline-primary">Lihat</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">Belum ada riwayat asesmen.</div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>