<?php
session_start();
include "koneksi.php";

// ===========================
// 1. AMBIL DATA DARI FORM
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
// 2. VALIDASI DATA
// ===========================
if (
    !$nama_pekerjaan || 
    !$ket_pekerjaan ||
    !$id_jurusan ||  // PERBAIKAN 2: Validasi id_jurusan
    !$pk_autonomy || !$pk_security || !$pk_tf ||
    !$pk_gm || !$pk_ec || !$pk_service || !$pk_challenge || !$pk_lifestyle
) {
    echo "<script>alert('Data tidak lengkap! Pastikan Nama Pekerjaan dan Jurusan dipilih.'); window.history.back();</script>";
    exit;
}

// ===========================
// 2.5 CEK DUPLIKASI DATA
// ===========================
// PERBAIKAN 3: Query menggunakan id_jurusan
$check_query = "SELECT id_pekerjaan FROM profil_pekerjaan WHERE nama_pekerjaan = ? AND id_jurusan = ?";
$stmt_check = mysqli_prepare($koneksi, $check_query);

if ($stmt_check) {
    // PERBAIKAN 4: Bind param "si" (String nama, Integer id_jurusan)
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


// ===========================
// 3. SIMPAN KE TABEL
// ===========================
// PERBAIKAN 5: Query Insert menggunakan id_jurusan
$query = "INSERT INTO profil_pekerjaan 
            (nama_pekerjaan, ket_pekerjaan, id_jurusan, pk_autonomy,
            pk_security, pk_tf, pk_gm, pk_ec, pk_service, pk_challenge, pk_lifestyle)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($koneksi, $query);

if (!$stmt) {
    die("Query Insert gagal: " . mysqli_error($koneksi));
}

// PERBAIKAN 6: Bind param "siiiiiiiii"
// (s = string untuk nama_pekerjaan)
// (i = integer pertama untuk id_jurusan)
// (8 i berikutnya untuk nilai skor)
mysqli_stmt_bind_param(
    $stmt,
    "ssiiiiiiiii", 
    $nama_pekerjaan,
    $ket_pekerjaan,
    $id_jurusan, // <-- Masuk sebagai integer
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

// ===========================
// 4. HASIL & REDIRECT
// ===========================
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