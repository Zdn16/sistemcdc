<?php
include "koneksi.php";

$nama   = $_POST['nama_user'];
$jk     = $_POST['jk_user'];
$email  = $_POST['email_user'];
$pass   = $_POST['pass_user']; // PASSWORD ASLI
$role   = $_POST['role'];

// Cek email duplicate
$cek = mysqli_query(
  $koneksi,
  "SELECT id_user FROM user WHERE email_user='$email'"
);

if (mysqli_num_rows($cek) > 0) {
  header("Location: user_tambah.php?error=duplicate");
  exit;
}

// Simpan TANPA enkripsi
$query = "INSERT INTO user 
          (nama_user, jk_user, email_user, pass_user, role)
          VALUES 
          ('$nama', '$jk', '$email', '$pass', '$role')";

mysqli_query($koneksi, $query);

header("Location: user.php?status=sukses");
