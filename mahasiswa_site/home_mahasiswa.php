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
$sql_konseling = "SELECT k.*, a.tanggal_asesmen 
                  FROM konseling k 
                  JOIN asesmen a ON k.id_asesmen = a.id_asesmen 
                  WHERE a.nim = '$nim' 
                  ORDER BY k.id_konseling DESC";

$query_konseling = mysqli_query($koneksi, $sql_konseling);

if (!$query_konseling) {
    die("Error Query Konseling: " . mysqli_error($koneksi));
}


// 4. Ambil Status Konseling Terakhir
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

// JIKA MAHASISWA PUNYA ID_JURUSAN
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
$sql_asesmen = "SELECT * FROM asesmen WHERE nim = '$nim' ORDER BY id_asesmen DESC";
$query_asesmen = mysqli_query($koneksi, $sql_asesmen);

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
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../foto/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_home.css">
    
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom mb-5">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="#">
            <i class="fas fa-university me-2"></i>CDC Mahasiswa
        </a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3 d-none d-md-block">Halo, <strong><?php echo htmlspecialchars($mhs['nama']); ?></strong></span>
            <a href="login_mahasiswa.php" class="btn btn-sm btn-light text-primary fw-bold rounded-pill px-3 shadow-sm">
                <i class="fas fa-sign-out-alt me-1"></i> Keluar
            </a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    
    <div class="row g-4"> <div class="col-lg-4">
            
            <div class="card card-dashboard text-center p-4">
                <div class="card-body p-0">
                    <?php
                        $foto_profil = "https://ui-avatars.com/api/?name=" . urlencode($mhs['nama']) . "&background=random&size=128";
                        if (!empty($mhs['foto']) && file_exists("uploads/" . $mhs['foto'])) {
                            $foto_profil = "uploads/" . $mhs['foto'];
                        }
                    ?>

                    <div class="profile-img-container mb-3">
                        <img src="<?php echo $foto_profil; ?>" 
                            class="rounded-circle shadow-sm" 
                            width="100" 
                            height="100" 
                            style="object-fit: cover;">
                    </div>

                    <h5 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($mhs['nama']); ?></h5>
                    <p class="text-muted small mb-4 bg-light d-inline-block px-3 py-1 rounded-pill">
                        <?php echo htmlspecialchars($mhs['nim']); ?>
                    </p>
                    
                    <ul class="list-group list-group-flush text-start mb-4">
                        <li class="list-group-item">
                            <i class="fas fa-envelope text-primary"></i> 
                            <?php echo htmlspecialchars($mhs['email']); ?>
                        </li>
                        
                        <li class="list-group-item">
                            <i class="fas fa-phone text-success"></i> 
                            <?php echo htmlspecialchars($mhs['no_hp'] ?? '-'); ?>
                        </li>
                        
                        <li class="list-group-item">
                            <i class="fas fa-graduation-cap text-warning"></i> 
                            <?php echo htmlspecialchars($nama_jurusan); ?>
                        </li>

                        <li class="list-group-item">
                            <i class="fas fa-venus-mars text-info"></i> 
                            <?php echo htmlspecialchars($mhs['jenis_kelamin']); ?>
                        </li>

                        <li class="list-group-item">
                            <i class="fas fa-calendar-alt text-danger"></i> 
                            Angkatan <?php echo htmlspecialchars($mhs['angkatan']); ?>
                        </li>

                        <li class="list-group-item">
                            <i class="fas fa-user-tag text-primary"></i> 
                            Status <?php echo htmlspecialchars($mhs['status_mahasiswa']); ?>
                        </li>
                    </ul>

                    <a href="edit_profil.php" class="btn btn-outline-primary w-100 rounded-pill fw-bold">
                        <i class="fas fa-edit me-1"></i> Edit Profil
                    </a>
                </div>
            </div>

            <div class="d-grid gap-3">
                <a href="asesmen2.php" class="btn btn-action text-start shadow-sm d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fs-5 fw-bold">Mulai Asesmen</div>
                        <small style="opacity: 0.9;">Cek potensi karirmu sekarang</small>
                    </div>
                    <i class="fas fa-clipboard-check fa-2x opacity-50"></i>
                </a>
            </div>

        </div>

        <div class="col-lg-8">
            
            <div class="row mb-2 g-3">
                <div class="col-md-6">
                    <div class="card card-dashboard h-100 border-0 shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                                    <i class="fas fa-tasks fa-2x"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Asesmen Selesai</h6>
                                <h2 class="mb-0 fw-bold"><?php echo mysqli_num_rows($query_asesmen); ?> <small class="fs-6 text-muted fw-normal">kali</small></h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-dashboard h-100 border-0 shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                                    <i class="fas fa-comments fa-2x"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Status Konseling</h6>
                                <h4 class="mb-0 fw-bold text-success"><?php echo ucfirst($status_text); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-dashboard p-2">
                <div class="card-header bg-transparent border-0 pb-0 pt-3 px-4">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="konseling-tab" data-bs-toggle="tab" data-bs-target="#konseling" type="button">
                                <i class="fas fa-history me-2"></i>Riwayat Konseling
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="asesmen-tab" data-bs-toggle="tab" data-bs-target="#asesmen" type="button">
                                <i class="fas fa-chart-bar me-2"></i>Riwayat Asesmen
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body px-4 py-4">
                    <div class="tab-content" id="myTabContent">
                        
                        <div class="tab-pane fade show active" id="konseling" role="tabpanel">
                            <?php if (mysqli_num_rows($query_konseling) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Topik</th>
                                                <th>Jadwal</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($query_konseling as $row): ?>
                                            <tr>
                                                <td class="text-muted fw-bold" style="font-size:0.9em;">
                                                    <?php echo date('d M Y', strtotime($row['tanggal_asesmen'])); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['catatan']); ?></td>
                                                <td>
                                                    <?php if($row['jadwal_konseling']): ?>
                                                        <span class="badge bg-light text-dark border">
                                                            <i class="far fa-clock me-1"></i>
                                                            <?php echo date('d M H:i', strtotime($row['jadwal_konseling'])); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $st = strtolower($row['status']);
                                                    $badgeClass = 'bg-secondary';
                                                    if ($st == 'menunggu') $badgeClass = 'bg-warning text-dark';
                                                    if ($st == 'terjadwal') $badgeClass = 'bg-info text-dark';
                                                    if ($st == 'selesai') $badgeClass = 'bg-success';
                                                    if ($st == 'ditolak') $badgeClass = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?> status-badge"><?php echo ucfirst($row['status']); ?></span>
                                                </td>
                                                <td>
                                                    <a href="detail_mahasiswa.php?id=<?php echo $row['id_asesmen']; ?>" class="btn btn-sm btn-light text-primary border rounded-circle shadow-sm" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="80" class="mb-3 opacity-25" alt="Empty">
                                    <p class="text-muted fw-bold">Belum ada riwayat konseling.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tab-pane fade" id="asesmen" role="tabpanel">
                             <?php if (mysqli_num_rows($query_asesmen) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Skor</th>
                                                <th>Kesimpulan</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($query_asesmen as $row): ?>
                                            <tr>
                                                <td class="text-muted fw-bold" style="font-size:0.9em;">
                                                    <?php echo date('d M Y', strtotime($row['tanggal_asesmen'])); ?>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                    <?php 
                                                    // LOGIC PERHITUNGAN SKOR (TIDAK DIUBAH)
                                                    $hasil = strtolower(trim($row['hasil_asesmen']));
                                                    $nilai_skor = 0;
                                                    switch ($hasil) {
                                                        case 'autonomy': $nilai_skor = $row['skor_autonomy']; break;
                                                        case 'security': $nilai_skor = $row['skor_security']; break;
                                                        case 'tf': $nilai_skor = $row['skor_tf']; break;
                                                        case 'gm': $nilai_skor = $row['skor_gm']; break;
                                                        case 'ec': $nilai_skor = $row['skor_ec']; break;
                                                        case 'service': $nilai_skor = $row['skor_service']; break;
                                                        case 'challenge': $nilai_skor = $row['skor_challenge']; break;
                                                        case 'lifestyle': $nilai_skor = $row['skor_lifestyle']; break;
                                                        default: $nilai_skor = 0;
                                                    }
                                                    echo htmlspecialchars($nilai_skor); 
                                                    ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars(substr($row['hasil_asesmen'], 0, 40)) . (strlen($row['hasil_asesmen']) > 40 ? '...' : ''); ?>
                                                </td>
                                                <td>
                                                    <a href="detail_mahasiswa.php?id=<?php echo $row['id_asesmen']; ?>" class="btn btn-sm btn-light text-primary border rounded-circle shadow-sm" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486820.png" width="80" class="mb-3 opacity-25" alt="Empty">
                                    <p class="text-muted fw-bold">Belum ada riwayat asesmen.</p>
                                </div>
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