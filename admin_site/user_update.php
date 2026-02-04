<?php
include "koneksi.php";

$id_user = $_POST['id_user'];
$status  = $_POST['status'];

$query = "UPDATE user SET status = '$status' WHERE id_user = '$id_user'";

if (mysqli_query($koneksi, $query)) {
    echo "<script>
            alert('Status pengguna berhasil diupdate!');
            window.location='user.php';
          </script>";
} else {
    echo "Error: " . mysqli_error($koneksi);
}
?>