<?php
// Pastikan session dimulai PALING ATAS sebelum include apapun
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include "koneksi.php";
include "konselor_navbar.php";

// CEK LOGIN & AMBIL ID SESSION
if (!isset($_SESSION['id_user'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='login.php';</script>";
    exit;
}

$id_konselor_login = $_SESSION['id_user']; // ID Konselor yang sedang login

// ==========================================
// LOGIKA FILTER WAKTU
// ==========================================
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$whereWaktu = ""; // Kita pakai satu variabel where saja biar rapi
$textFilter = "Semua Data";

// Kolom tanggal di tabel KONSELING (Sesuaikan jika nama kolom beda, misal: tanggal_konseling)
$colTgl = "tanggal_konseling"; 

switch ($filter) {
    case '3bulan':
        $whereWaktu = "AND $colTgl >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        $textFilter = "3 Bulan Terakhir";
        break;
    case '6bulan':
        $whereWaktu = "AND $colTgl >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        $textFilter = "6 Bulan Terakhir";
        break;
    case '1tahun':
        $whereWaktu = "AND $colTgl >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $textFilter = "1 Tahun Terakhir";
        break;
}

// ==========================================
// QUERY DATA (KHUSUS KONSELOR LOGIN)
// ==========================================

// Konseling Terjadwal (Status Disetujui & Milik Konselor Ini)
$q2 = "SELECT COUNT(*) AS total FROM konseling 
       WHERE status='Disetujui' 
       AND id_user= '$id_konselor_login' 
       $whereWaktu";
$res2 = $koneksi->query($q2);
$totalTerjadwal = $res2 ? $res2->fetch_assoc()['total'] : 0;

// C. Konseling Selesai (Status Selesai & Milik Konselor Ini)
$q3 = "SELECT COUNT(*) AS total FROM konseling 
       WHERE status='Selesai' 
       AND id_user = '$id_konselor_login' 
       $whereWaktu";
$res3 = $koneksi->query($q3);
$totalSelesai = $res3 ? $res3->fetch_assoc()['total'] : 0;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Konselor</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div id="content">
  <div class="container-fluid p-4">
    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Dashboard Saya</h1>
            <p class="mb-0 text-muted">Data Statistik: <strong><?= $textFilter ?></strong></p>
        </div>
        
        <form method="GET" action="" class="d-none d-sm-inline-block form-inline ml-auto shadow-sm">
            <div class="input-group">
                <select name="filter" class="form-select form-select-sm bg-white border-0" onchange="this.form.submit()" style="cursor:pointer;">
                    <option value="semua" <?= $filter == 'semua' ? 'selected' : '' ?>>Semua Waktu</option>
                    <option value="3bulan" <?= $filter == '3bulan' ? 'selected' : '' ?>>3 Bulan Terakhir</option>
                    <option value="6bulan" <?= $filter == '6bulan' ? 'selected' : '' ?>>6 Bulan Terakhir</option>
                    <option value="1tahun" <?= $filter == '1tahun' ? 'selected' : '' ?>>1 Tahun Terakhir</option>
                </select>
                <div class="input-group-append">
                    <button class="btn btn-primary btn-sm" type="button"><i class="fas fa-filter fa-sm"></i></button>
                </div>
            </div>
        </form>
    </div>

    <div class="row mb-4">

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-4 border-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Jadwal Disetujui</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalTerjadwal ?></div>
                            <a href="konselor_asesmen.php?status=Disetujui" class="text-xs font-weight-bold text-info text-decoration-none">
                                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                        <div class="col-auto"><i class="fas fa-calendar-check fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-4 border-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Konseling Selesai</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalSelesai ?></div>
                            <a href="konselor_asesmen.php?status=Selesai" class="text-xs font-weight-bold text-success text-decoration-none">
                                Lihat Riwayat <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                        <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
  </div>
</div>
<script>
  const toggleBtn = document.getElementById("toggleSidebar");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    content.classList.toggle("shift");
  });
</script>

</body>
</html>