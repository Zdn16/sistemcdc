<?php
session_start();
include "koneksi.php";

// ===========================
// AMBIL DATA DARI FORM
// ===========================
$nama_pekerjaan = $_POST['nama_pekerjaan'] ?? '';
$ket_pekerjaan  = $_POST['ket_pekerjaan'] ?? '';
$id_jurusan     = $_POST['id_jurusan'] ?? ''; 
$pk_autonomy    = $_POST['pk_autonomy'] ?? '';
$pk_security    = $_POST['pk_security'] ?? '';
$pk_tf          = $_POST['pk_tf'] ?? '';
$pk_gm          = $_POST['pk_gm'] ?? '';
$pk_ec          = $_POST['pk_ec'] ?? '';
$pk_service     = $_POST['pk_service'] ?? '';
$pk_challenge   = $_POST['pk_challenge'] ?? '';
$pk_lifestyle   = $_POST['pk_lifestyle'] ?? '';

// ===========================
// VALIDASI DATA
// ===========================
if (empty($nama_pekerjaan) || empty($id_jurusan) || empty($pk_autonomy)) {
    echo "<script>alert('Data tidak lengkap! Harap isi semua form dan pilih skor.'); window.history.back();</script>";
    exit;
}

// ===========================
// CEK DUPLIKASI DATA
// ===========================
$check_query = "SELECT id_pekerjaan FROM profil_pekerjaan WHERE nama_pekerjaan = ? AND id_jurusan = ?";
$stmt_check = mysqli_prepare($koneksi, $check_query);

if ($stmt_check) {
    mysqli_stmt_bind_param($stmt_check, "si", $nama_pekerjaan, $id_jurusan);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        echo "<script>
                alert('Gagal! Pekerjaan tersebut sudah ada di departemen yang dipilih.'); 
                window.history.back();
              </script>";
        mysqli_stmt_close($stmt_check);
        exit;
    }
    mysqli_stmt_close($stmt_check);
}

// ===========================
// SIMPAN KE DATABASE
// ===========================
$query = "INSERT INTO profil_pekerjaan 
            (nama_pekerjaan, ket_pekerjaan, id_jurusan, pk_autonomy,
            pk_security, pk_tf, pk_gm, pk_ec, pk_service, pk_challenge, pk_lifestyle)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($koneksi, $query);

if (!$stmt) {
    die("Query Insert gagal: " . mysqli_error($koneksi));
}

// Tipe data: s (string), s (string), i (int) ... sisanya int
mysqli_stmt_bind_param(
    $stmt,
    "ssiiiiiiiii", 
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
    $pk_lifestyle
);

$execute = mysqli_stmt_execute($stmt);

if($execute){
    mysqli_stmt_close($stmt);
    echo "<script>
            alert('Profil Pekerjaan Berhasil Ditambahkan!'); 
            window.location='admin_pekerjaan.php';
          </script>";
} else {
    die("Gagal menambah pekerjaan: " . mysqli_error($koneksi));
}
?>