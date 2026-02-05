<?php
session_start();

$permasalahan = $_POST['permasalahan'] ?? '';
$permasalahan = trim($permasalahan);

if ($permasalahan === '') {
    $_SESSION['error_permasalahan'] = "Permasalahan kosong. Silakan isi terlebih dahulu.";
    header("Location: asesmen2.php"); // ganti ke halaman input permasalahan kamu
    exit;
}

// Hitung jumlah kata
$kata = preg_split('/\s+/', $permasalahan, -1, PREG_SPLIT_NO_EMPTY);
$jumlah_kata = count($kata);

if ($jumlah_kata < 5) {
    $_SESSION['error_permasalahan'] = "Ceritakan lebih detail ya. Minimal 5 kata (sekarang $jumlah_kata kata).";
    $_SESSION['old_permasalahan'] = $permasalahan; // supaya input tidak hilang
    header("Location: asesmen2.php"); // ganti ke halaman input permasalahan kamu
    exit;
}

// Lolos validasi
$_SESSION['permasalahan'] = $permasalahan;

header("Location: asesmen3.php");
exit;
