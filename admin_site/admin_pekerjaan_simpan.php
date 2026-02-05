<?php
session_start();
include "koneksi.php";

// ===========================
// AMBIL DATA DARI FORM
// ===========================
$id_pekerjaan   = $_POST['id_pekerjaan'] ?? null;
$nama_pekerjaan = $_POST['nama_pekerjaan'] ?? null;
$ket_pekerjaan  = $_POST['ket_pekerjaan'] ?? null;
$id_jurusan     = $_POST['id_jurusan'] ?? null; 
$pk_autonomy    = $_POST['pk_autonomy'] ?? null;
$pk_security    = $_POST['pk_security'] ?? null;
$pk_tf          = $_POST['pk_tf'] ?? null;
$pk_gm          = $_POST['pk_gm'] ?? null;
$pk_ec          = $_POST['pk_ec'] ?? null;
$pk_service     = $_POST['pk_service'] ?? null;
$pk_challenge   = $_POST['pk_challenge'] ?? null;
$pk_lifestyle   = $_POST['pk_lifestyle'] ?? null;


// ===========================
// VALIDASI DATA
// ===========================
if (
    !$nama_pekerjaan || 
    !$ket_pekerjaan ||
    !$id_jurusan ||  
    !$pk_autonomy || !$pk_security || !$pk_tf ||
    !$pk_gm || !$pk_ec || !$pk_service || !$pk_challenge || !$pk_lifestyle
) {
    echo "<script>alert('Data tidak lengkap! Pastikan Nama Pekerjaan dan Jurusan dipilih.'); window.history.back();</script>";
    exit;
}

// ===========================
// CEK DUPLIKASI DATA
$check_query = "SELECT id_pekerjaan FROM profil_pekerjaan WHERE nama_pekerjaan = ? AND id_jurusan = ?";
$stmt_check = mysqli_prepare($koneksi, $check_query);

if ($stmt_check) {
    mysqli_stmt_bind_param($stmt_check, "si", $nama_pekerjaan, $id_jurusan);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        echo "<script>
                alert('Gagal! Pekerjaan tersebut sudah ada di jurusan yang dipilih.'); 
                window.history.back();
              </script>";
        mysqli_stmt_close($stmt_check);
        exit;
    }
    mysqli_stmt_close($stmt_check);
} else {
    die("Query Cek Gagal: " . mysqli_error($koneksi));
}

// Menyimpan ke tabel
$query = "INSERT INTO profil_pekerjaan 
            (nama_pekerjaan, ket_pekerjaan, id_jurusan, pk_autonomy,
            pk_security, pk_tf, pk_gm, pk_ec, pk_service, pk_challenge, pk_lifestyle)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($koneksi, $query);

if (!$stmt) {
    die("Query Insert gagal: " . mysqli_error($koneksi));
}


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
    exit;
} else {
    die("Gagal menambah pekerjaan: " . mysqli_error($koneksi));
}
?>