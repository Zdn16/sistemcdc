<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sistemcdc"; // sesuaikan

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(["error" => "db_connect_failed"]);
  exit;
}
