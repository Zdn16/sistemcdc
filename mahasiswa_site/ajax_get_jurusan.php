<?php
include "koneksi_ajax.php";

header('Content-Type: application/json; charset=utf-8');

// biar warning tidak merusak JSON 
error_reporting(0);

$id_fakultas = isset($_GET['id_fakultas']) ? (int)$_GET['id_fakultas'] : 0;

if ($id_fakultas <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = mysqli_prepare($koneksi, "
    SELECT id_jurusan, nama_jurusan
    FROM jurusan
    WHERE id_fakultas = ?
    ORDER BY nama_jurusan ASC
");

if (!$stmt) {
    echo json_encode(["error" => "prepare_failed", "detail" => mysqli_error($koneksi)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_fakultas);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        "id_jurusan" => (int)$row["id_jurusan"],
        "nama_jurusan" => $row["nama_jurusan"]
    ];
}

echo json_encode($data);
