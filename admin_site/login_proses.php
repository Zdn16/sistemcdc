<?php
session_start();
include "koneksi.php";

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 1. TAMBAHKAN 'status' DI DALAM SELECT
$stmt = $koneksi->prepare("SELECT id_user, nama_user, role, pass_user, status FROM user WHERE email_user = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // 2. CEK STATUS SEBELUM CEK PASSWORD (ATAU BERSAMAAN)
    if ($user['status'] == 0) {
        // Jika status 0 (Nonaktif), tolak login
        header("Location: login.php?error=inactive");
        exit;
    }

    // pass_user sekarang berisi hash bcrypt
    if (password_verify($password, $user['pass_user'])) {

        // opsional: upgrade hash kalau algoritma berubah di masa depan
        if (password_needs_rehash($user['pass_user'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $up = $koneksi->prepare("UPDATE user SET pass_user=? WHERE id_user=?");
            $up->bind_param("si", $newHash, $user['id_user']);
            $up->execute();
        }

        $_SESSION['id_user']   = $user['id_user'];
        $_SESSION['nama_user'] = $user['nama_user'];
        $_SESSION['role']      = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: dashboard.php");
        } else {
            header("Location: konselor_dashboard.php");
        }
        exit;
    }
}

header("Location: login.php?error=login");
exit;