<?php
session_start();
include "koneksi.php";

// ====== PHPMailer ======
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$email = $_POST['email'] ?? '';
$email = trim($email);

if ($email === '') {
  header("Location: forgot_password.php?msg=fail");
  exit;
}

// Cari user berdasarkan email
$stmt = $koneksi->prepare("SELECT id_user, nama_user, email_user FROM user WHERE email_user=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows !== 1) {
  header("Location: forgot_password.php?msg=notfound");
  exit;
}

$user = $res->fetch_assoc();

mysqli_query($koneksi, "SET time_zone = '+07:00'");

// Generate token asli (yang dikirim via email)
$token = bin2hex(random_bytes(32)); // 64 char
$tokenHash = hash('sha256', $token);
$expire = date('Y-m-d H:i:s', time() + 60*30); // 30 menit

// Simpan hash token + expiry di tabel user
$up = $koneksi->prepare("UPDATE user SET reset_token_hash=?, reset_token_expire=? WHERE id_user=?");
$up->bind_param("ssi", $tokenHash, $expire, $user['id_user']);
$up->execute();

// Buat link reset (sesuaikan domain/path project kamu)
$baseUrl = "http://localhost/sistemcdc"; 
$resetLink = $baseUrl . "/admin_site/resetpassword.php?token=" . urlencode($token) . "&email=" . urlencode($user['email_user']);

try {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host       = "smtp.gmail.com";
  $mail->SMTPAuth   = true;
  $mail->Username   = "cdcuniversitasandalas@gmail.com";
  $mail->Password   = "wmjb qouw dcnp ixmw"; // app password, bukan password biasa
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = 587;

  $mail->setFrom("cdcuniversitasandalas@gmail.com", "CDC Universitas Andalas");
  $mail->addAddress($user['email_user'], $user['nama_user']);

  $mail->isHTML(true);
  $mail->Subject = "Reset Password Akun CDC";
  $mail->Body = "
    <p>Halo <b>{$user['nama_user']}</b>,</p>
    <p>Kami menerima permintaan reset password. Klik tombol/link berikut untuk membuat password baru (berlaku 30 menit):</p>
    <p><a href='{$resetLink}' style='display:inline-block;padding:10px 14px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px;'>Reset Password</a></p>
    <p>Jika kamu tidak merasa melakukan permintaan ini, abaikan email ini.</p>
  ";

  $mail->SMTPOptions = [
  'ssl' => [
    'verify_peer' => false,
    'verify_peer_name' => false,
    'allow_self_signed' => true,
  ],
];

  $mail->send();
  header("Location: forgot_password.php?msg=sent");
  exit;

} catch (Exception $e) {
  echo "Mailer Error: " . $mail->ErrorInfo;
  exit;
}
 {
  // Kalau email gagal terkirim, sebaiknya token dibatalkan
  $clr = $koneksi->prepare("UPDATE user SET reset_token_hash=NULL, reset_token_expire=NULL WHERE id_user=?");
  $clr->bind_param("i", $user['id_user']);
  $clr->execute();

  header("Location: forgot_password.php?msg=fail");
  exit;
}
