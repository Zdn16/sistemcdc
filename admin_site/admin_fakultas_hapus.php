<?php
include "koneksi.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "DELETE FROM fakultas WHERE id_fakultas = '$id'";
    $result = mysqli_query($koneksi, $query);

    if ($result) {
        header("Location: admin_fakultas.php?status=deleted");
    } else {
        echo "Gagal menghapus data: " . mysqli_error($koneksi);
    }
}
?>
