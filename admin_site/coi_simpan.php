<?php
include "koneksi.php";

$pertanyaan = trim($_POST['pertanyaan']);
$kategori   = trim($_POST['kategori']);

$query = "INSERT INTO item_pertanyaan (pertanyaan, kategori)
          VALUES ('$pertanyaan', '$kategori')";

if (!mysqli_query($koneksi, $query)) {
    die("ERROR MYSQL: " . mysqli_error($koneksi));
}

header("Location: coi.php?status=sukses");