<?php
include "koneksi.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "DELETE FROM profil_pekerjaan WHERE id_pekerjaan = '$id'";
    $result = mysqli_query($koneksi, $query);

    if ($result) {
        header("Location: admin_pekerjaan.php?status=deleted");
    } else {
        echo "Gagal menghapus data: " . mysqli_error($koneksi);
    }
}
?>
