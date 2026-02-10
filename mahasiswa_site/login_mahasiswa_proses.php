<?php
session_start();
include "koneksi.php";

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 1. QUERY KE TABEL MAHASISWA
// Pastikan kolom di database: nim, nama, email, password
$stmt = $koneksi->prepare("SELECT nim, nama, password FROM mahasiswa WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $mhs = $result->fetch_assoc();

    // 2. VERIFIKASI PASSWORD
    if (password_verify($password, $mhs['password'])) {
        
        // Regenerate session ID untuk keamanan (mencegah session fixation)
        session_regenerate_id(true);

        // 3. SET SESSION (Sesuai permintaan: NIM dan Nama)
        $_SESSION['nim']  = $mhs['nim'];
        $_SESSION['nama'] = $mhs['nama'];
        $_SESSION['role'] = 'mahasiswa'; // Opsional: penanda role
        $_SESSION['login_status'] = true;

        // 4. REDIRECT SUKSES
        header("Location: home_mahasiswa.php");
        exit;
    }
}

// 5. JIKA GAGAL (Email tidak ada atau Password salah)
header("Location: login_mahasiswa.php?error=login");
exit;
?>