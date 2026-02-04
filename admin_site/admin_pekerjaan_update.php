<?php
session_start();
include "koneksi.php";

$id = $_POST['id_pekerjaan'] ?? null;

if (!$id) {
    die("ID tidak valid");
}

// 1. Tangkap Data
$nama_pekerjaan = $_POST['nama_pekerjaan'];
$id_jurusan     = $_POST['id_jurusan']; // Ganti dari jurusan_pekerjaan ke id_jurusan
$ket_pekerjaan  = $_POST['ket_pekerjaan'];
$pk_autonomy    = $_POST['pk_autonomy'];
$pk_security    = $_POST['pk_security'];
$pk_tf          = $_POST['pk_tf'];
$pk_gm          = $_POST['pk_gm'];
$pk_ec          = $_POST['pk_ec'];
$pk_service     = $_POST['pk_service'];
$pk_challenge   = $_POST['pk_challenge'];
$pk_lifestyle   = $_POST['pk_lifestyle'];

// 2. CEK DUPLIKASI (PENTING!)
// Cek apakah ada pekerjaan LAIN dengan nama & jurusan sama (Kecuali ID yang sedang diedit)
$cek_query = "SELECT id_pekerjaan FROM profil_pekerjaan 
              WHERE nama_pekerjaan = ? 
              AND id_jurusan = ? 
              AND id_pekerjaan != ?"; // Pastikan tidak mengecek dirinya sendiri

$stmt_cek = mysqli_prepare($koneksi, $cek_query);
mysqli_stmt_bind_param($stmt_cek, "sii", $nama_pekerjaan, $id_jurusan, $id);
mysqli_stmt_execute($stmt_cek);
mysqli_stmt_store_result($stmt_cek);

if (mysqli_stmt_num_rows($stmt_cek) > 0) {
    echo "<script>
            alert('Gagal Update! Nama Pekerjaan tersebut sudah ada di jurusan ini.');
            window.history.back();
          </script>";
    mysqli_stmt_close($stmt_cek);
    exit;
}
mysqli_stmt_close($stmt_cek);


// 3. PROSES UPDATE
// Ubah kolom jurusan_pekerjaan jadi id_jurusan
$query = "UPDATE profil_pekerjaan SET
    nama_pekerjaan = ?,
    ket_pekerjaan =?,
    id_jurusan = ?, 
    pk_autonomy = ?,
    pk_security = ?,
    pk_tf = ?,
    pk_gm = ?,
    pk_ec = ?,
    pk_service = ?,
    pk_challenge = ?,
    pk_lifestyle = ?
WHERE id_pekerjaan = ?";

$stmt = mysqli_prepare($koneksi, $query);

if (!$stmt) {
    die("Query Error: " . mysqli_error($koneksi));
}

// Bind Param:
// "s" (string) untuk nama
// "i" (int) untuk id_jurusan
// 8 "i" untuk skor
// 1 "i" terakhir untuk WHERE id_pekerjaan
// Total: "siiiiiiiiii" (1 s, 10 i)

mysqli_stmt_bind_param(
    $stmt,
    "ssiiiiiiiiii", 
    $nama_pekerjaan,
    $ket_pekerjaan,
    $id_jurusan,
    $pk_autonomy,
    $pk_security,
    $pk_tf,
    $pk_gm,
    $pk_ec,
    $pk_service,
    $pk_challenge,
    $pk_lifestyle,
    $id
);

$execute = mysqli_stmt_execute($stmt);

if ($execute) {
    echo "<script>
        alert('Data berhasil diperbarui');
        window.location='admin_pekerjaan.php';
    </script>";
} else {
    die("Gagal update: " . mysqli_error($koneksi));
}
?>