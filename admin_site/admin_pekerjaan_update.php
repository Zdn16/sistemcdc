<?php
session_start();
include "koneksi.php";

$id = $_POST['id_pekerjaan'] ?? null;

if (!$id) {
    die("ID Pekerjaan tidak valid.");
}

// Tangkap Data dari Form
$nama_pekerjaan = $_POST['nama_pekerjaan'];
$ket_pekerjaan  = $_POST['ket_pekerjaan'];
$id_jurusan     = $_POST['id_jurusan'];
$pk_autonomy    = $_POST['pk_autonomy'];
$pk_security    = $_POST['pk_security'];
$pk_tf          = $_POST['pk_tf'];
$pk_gm          = $_POST['pk_gm'];
$pk_ec          = $_POST['pk_ec'];
$pk_service     = $_POST['pk_service'];
$pk_challenge   = $_POST['pk_challenge'];
$pk_lifestyle   = $_POST['pk_lifestyle'];

// ==========================================
// CEK DUPLIKASI (KECUALI DIRI SENDIRI)
// ==========================================
$cek_query = "SELECT id_pekerjaan FROM profil_pekerjaan 
              WHERE nama_pekerjaan = ? 
              AND id_jurusan = ? 
              AND id_pekerjaan != ?"; // Pastikan tidak mengecek data yang sedang diedit

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


// ==========================================
// PROSES UPDATE DATA
// ==========================================
$query = "UPDATE profil_pekerjaan SET
    nama_pekerjaan = ?,
    ket_pekerjaan = ?,
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

// Parameter: 
// s (nama), s (ket), i (id_jur), 
// i (auto), i (sec), i (tf), i (gm), i (ec), i (serv), i (chal), i (life), 
// i (WHERE id)
// Total: 2 string, 10 integer = "ssiiiiiiiiii"

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