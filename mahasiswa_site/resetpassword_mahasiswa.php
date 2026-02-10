<?php
include "koneksi.php";

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$msg   = $_GET['msg'] ?? '';

$valid = false;

if ($email && $token) {
  $tokenHash = hash('sha256', $token);

  $stmt = $koneksi->prepare("
    SELECT nim, reset_token_expire 
    FROM mahasiswa 
    WHERE email=? AND reset_token_hash=? 
    LIMIT 1
  ");
  $stmt->bind_param("ss", $email, $tokenHash);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res && $res->num_rows === 1) {
    $u = $res->fetch_assoc();
    // cek expiry
    if (!empty($u['reset_token_expire']) && strtotime($u['reset_token_expire']) >= time()) {
      $valid = true;
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password</title>
  <link rel="icon" type="image/png" href="../foto/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:520px;">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h4 class="mb-3">Reset Password</h4>

      <?php if (!$valid): ?>
        <div class="alert alert-danger">
          Link reset tidak valid atau sudah kedaluwarsa.
        </div>
        <a class="btn btn-secondary w-100" href="forgot_password_mahasiswa.php">Minta Link Baru</a>

      <?php else: ?>

        <?php if ($msg === 'mismatch'): ?>
          <div class="alert alert-danger">Password dan konfirmasi tidak sama.</div>
        <?php elseif ($msg === 'weak'): ?>
          <div class="alert alert-danger">Password minimal 8 karakter.</div>
        <?php elseif ($msg === 'fail'): ?>
          <div class="alert alert-danger">Gagal reset password. Coba lagi.</div>
        <?php endif; ?>

        <form action="resetpassword_mahasiswa_proses.php" method="post">
          <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

          <div class="mb-3">
            <label class="form-label">Password Baru</label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>

          <button class="btn btn-primary w-100" type="submit">Simpan Password Baru</button>
        </form>

      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
