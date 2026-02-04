<?php
session_start();
include "koneksi.php";

// Pastikan login konselor
if(!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') != 'konselor'){
    header("Location: login.php");
    exit;
}

$id_user = (int)$_SESSION['id_user'];

// ===========================
// 1. AMBIL DATA DARI FORM
// ===========================
$id_konseling  = isset($_POST['id_konseling']) ? (int)$_POST['id_konseling'] : 0;
$status_baru   = $_POST['status_konseling'] ?? '';
$catatan_input = trim($_POST['catatan'] ?? '');

// ===========================
// 2. VALIDASI DASAR
// ===========================
if ($id_konseling <= 0 || $status_baru === '') {
    echo "<script>alert('Data tidak lengkap!'); window.history.back();</script>";
    exit;
}

if (!in_array($status_baru, ['Lanjutan', 'Selesai'], true)) {
    echo "<script>alert('Status tidak valid!'); window.history.back();</script>";
    exit;
}

if ($catatan_input === '') {
    echo "<script>alert('Catatan wajib diisi!'); window.history.back();</script>";
    exit;
}

// ===========================
// 3. AMBIL DATA LAMA (VALIDASI SESI AKTIF)
//    - pastikan konselor yang update adalah pemilik sesi
//    - pastikan status saat ini = Disetujui (sesi aktif)
// ===========================
$q_cek = mysqli_query($koneksi, "
    SELECT id_konseling, id_user, id_asesmen, sesi, status, catatan
    FROM konseling
    WHERE id_konseling = '$id_konseling'
    LIMIT 1
");

if (!$q_cek || mysqli_num_rows($q_cek) == 0) {
    echo "<script>alert('Data konseling tidak ditemukan!'); window.history.back();</script>";
    exit;
}

$d_lama = mysqli_fetch_assoc($q_cek);

// Cek pemilik konselor
if ((int)$d_lama['id_user'] !== $id_user) {
    echo "<script>alert('Anda tidak berhak mengubah data ini!'); window.history.back();</script>";
    exit;
}

// Hanya boleh update sesi aktif
if (($d_lama['status'] ?? '') !== 'Disetujui') {
    echo "<script>alert('Sesi ini sudah tidak aktif untuk diupdate (bukan status Disetujui).'); window.location='konselor_asesmen.php';</script>";
    exit;
}

$sesi_lama    = (int)($d_lama['sesi'] ?? 1);
$catatan_lama = $d_lama['catatan'] ?? '';

// ===========================
// 4. APPEND CATATAN
// ===========================
$catatan_fix = $catatan_input;

// ===========================
// 5. UPDATE DATABASE
//    - sesi TIDAK diubah
//    - status menjadi Lanjutan / Selesai
// ===========================
$query = "UPDATE konseling SET status = ?, catatan = ? WHERE id_konseling = ?";
$stmt  = mysqli_prepare($koneksi, $query);

if (!$stmt) {
    die("Query Error: " . mysqli_error($koneksi));
}

// bind: s (status), s (catatan), i (id)
mysqli_stmt_bind_param($stmt, "ssi", $status_baru, $catatan_fix, $id_konseling);
$execute = mysqli_stmt_execute($stmt);

if ($execute) {
    $msg = ($status_baru === 'Lanjutan')
        ? "Status diupdate ke Lanjutan. Menunggu admin menjadwalkan sesi berikutnya."
        : "Status diupdate ke Selesai. Konseling telah berakhir.";

    echo "<script>
            alert(" . json_encode($msg) . ");
            window.location='konselor_asesmen.php';
          </script>";
} else {
    echo "<script>alert('Gagal update!'); window.history.back();</script>";
}
?>
