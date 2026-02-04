<?php
$msg = $_GET['msg'] ?? '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lupa Password</title>
  <link rel="icon" type="image/png" href="../foto/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:520px;">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h4 class="mb-3">Lupa Password</h4>

      <?php if ($msg === 'sent'): ?>
        <div class="alert alert-success">Link reset password sudah dikirim ke email.</div>
      <?php elseif ($msg === 'notfound'): ?>
        <div class="alert alert-danger">Email tidak terdaftar.</div>
      <?php elseif ($msg === 'fail'): ?>
        <div class="alert alert-danger">Gagal mengirim email. Coba lagi.</div>
      <?php endif; ?>

      <form action="forgot_password_proses.php" method="post">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="Masukkan email terdaftar" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Kirim Link Reset</button>
      </form>

      <div class="mt-3 text-center">
        <a href="login.php" class="text-decoration-none">Kembali ke Login</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
