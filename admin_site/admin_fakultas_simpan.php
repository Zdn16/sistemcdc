<?php
session_start();
include "koneksi.php";

// ===========================
// AMBIL DATA DARI FORM
// ===========================
$id_fakultas   = $_POST['id_fakultas'] ?? null;
$nama_fakultas = $_POST['nama_fakultas'] ?? null;
$singkatan     = $_POST['singkatan'] ?? null;

// ===========================
// VALIDASI DATA KOSONG
// ===========================
if (!$nama_fakultas || !$singkatan) {
    echo "<script>alert('Data tidak lengkap!'); window.history.back();</script>";
    exit;
}

// ===========================
// CEK DUPLIKASI
// ===========================
$cek_query = mysqli_query($koneksi, "SELECT id_fakultas FROM fakultas WHERE nama_fakultas = '$nama_fakultas'");

if (mysqli_num_rows($cek_query) > 0) {
    header("Location: admin_fakultas_tambah.php?error=duplicate"); 
    exit; 
}

// ===========================
// SIMPAN KE TABEL FAKULTAS
// ===========================
$query = "INSERT INTO fakultas (nama_fakultas, singkatan) VALUES (?,?)";

$stmt = mysqli_prepare($koneksi, $query);

if (!$stmt) {
    die("Query gagal: " . mysqli_error($koneksi));
}

mysqli_stmt_bind_param($stmt, "ss", $nama_fakultas, $singkatan);

$execute = mysqli_stmt_execute($stmt);

// ===========================
// HASIL & REDIRECT
// ===========================
if($execute){
    mysqli_stmt_close($stmt);
    echo "<script>
            alert('Fakultas Berhasil Ditambahkan!'); 
            window.location='admin_fakultas.php';
          </script>";
    exit;
} else {
    die("Gagal menambah fakultas: " . mysqli_error($koneksi));
}
?>