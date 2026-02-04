<?php
include "koneksi.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "DELETE FROM jurusan WHERE id_jurusan = '$id'";
    $result = mysqli_query($koneksi, $query);

    if ($result) {
        header("Location: admin_jurusan.php?status=deleted");
    } else {
        echo "Gagal menghapus data: " . mysqli_error($koneksi);
    }
}
?>
