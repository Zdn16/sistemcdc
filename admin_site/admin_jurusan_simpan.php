<?php
session_start();
include "koneksi.php";

// ===========================
// 1. AMBIL DATA DARI FORM
// ===========================
$id_fakultas  = $_POST['id_fakultas'] ?? null;      // dari <select>
$nama_jurusan = $_POST['nama_jurusan'] ?? null;     // dari input jurusan

// rapikan input
$id_fakultas  = (int)$id_fakultas;
$nama_jurusan = trim($nama_jurusan ?? "");

// ===========================
// 2. VALIDASI DATA KOSONG
// ===========================
if ($id_fakultas <= 0 || $nama_jurusan === "") {
    echo "<script>alert('Data tidak lengkap!'); window.history.back();</script>";
    exit;
}

// ===========================
// [BARU] 2.5. CEK DUPLIKASI
// ===========================
// Cek: Apakah Nama Jurusan INI sudah ada di Fakultas INI?
// Kita escape string dulu biar aman untuk query cek manual
$nama_jurusan_esc = mysqli_real_escape_string($koneksi, $nama_jurusan);

$cek_duplikat = mysqli_query($koneksi, "
    SELECT id_jurusan 
    FROM jurusan 
    WHERE nama_jurusan = '$nama_jurusan_esc' 
      AND id_fakultas = '$id_fakultas'
");

if (mysqli_num_rows($cek_duplikat) > 0) {
    // Jika sudah ada, kembalikan ke form dengan pesan error
    // Pastikan 'admin_jurusan.php' sesuai nama file form Anda
    header("Location: admin_jurusan_tambah.php?error=duplicate");
    exit;
}

// ===========================
// 3. VALIDASI FAKULTAS EXIST
// ===========================
// Pastikan id_fakultas memang ada di tabel fakultas
$cek = mysqli_prepare($koneksi, "SELECT 1 FROM fakultas WHERE id_fakultas=?");
mysqli_stmt_bind_param($cek, "i", $id_fakultas);
mysqli_stmt_execute($cek);
mysqli_stmt_store_result($cek);
if (mysqli_stmt_num_rows($cek) == 0) {
    echo "<script>alert('Fakultas tidak valid!'); window.history.back();</script>";
    exit;
}
mysqli_stmt_close($cek);

// ===========================
// 4. SIMPAN KE TABEL JURUSAN
// ===========================
$query = "INSERT INTO jurusan (id_fakultas, nama_jurusan) VALUES (?, ?)";
$stmt  = mysqli_prepare($koneksi, $query);

if (!$stmt) {
    die("Prepare gagal: " . mysqli_error($koneksi));
}

mysqli_stmt_bind_param($stmt, "is", $id_fakultas, $nama_jurusan);

$execute = mysqli_stmt_execute($stmt);

// ===========================
// 5. HASIL & REDIRECT
// ===========================
if ($execute) {
    mysqli_stmt_close($stmt);
    echo "<script>
            alert('Departemen berhasil ditambahkan!');
            window.location='admin_jurusan.php';
          </script>";
    exit;
} else {
    die("Gagal menambah jurusan: " . mysqli_stmt_error($stmt));
}
?>