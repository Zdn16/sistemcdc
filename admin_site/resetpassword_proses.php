<?php
include "koneksi.php";

$email = $_POST['email'] ?? '';
$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

if (!$email || !$token) {
  header("Location: forgot_password.php?msg=fail");
  exit;
}

if (strlen($password) < 8) {
  header("Location: resetpassword.php?email=".urlencode($email)."&token=".urlencode($token)."&msg=weak");
  exit;
}

if ($password !== $confirm) {
  header("Location: resetpassword.php?email=".urlencode($email)."&token=".urlencode($token)."&msg=mismatch");
  exit;
}

$tokenHash = hash('sha256', $token);

// cek token masih valid
$stmt = $koneksi->prepare("
  SELECT id_user, reset_token_expire
  FROM user
  WHERE email_user=? AND reset_token_hash=?
  LIMIT 1
");
$stmt->bind_param("ss", $email, $tokenHash);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows !== 1) {
  header("Location: forgot_password.php?msg=fail");
  exit;
}

$u = $res->fetch_assoc();
if (empty($u['reset_token_expire']) || strtotime($u['reset_token_expire']) < time()) {
  header("Location: forgot_password.php?msg=fail");
  exit;
}

// update password (hash) + hapus token
$newHash = password_hash($password, PASSWORD_DEFAULT);

$up = $koneksi->prepare("
  UPDATE user
  SET pass_user=?, reset_token_hash=NULL, reset_token_expire=NULL
  WHERE id_user=?
");
$up->bind_param("si", $newHash, $u['id_user']);

if ($up->execute()) {
  header("Location: login.php?reset=success");
  exit;
}

header("Location: resetpassword.php?email=".urlencode($email)."&token=".urlencode($token)."&msg=fail");
exit;
