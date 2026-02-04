<?php
include "koneksi.php";
?>

<?php
$msg = '';
$alert_type = 'danger'; 

if (isset($_GET['error']) && $_GET['error'] === 'login') {
    $msg = 'Email atau password salah';
    $alert_type = 'danger'; 
}

if (isset($_GET['error']) && $_GET['error'] === 'inactive') {
    $msg = 'Akun Anda telah dinonaktifkan. Silakan hubungi Admin.';
    $alert_type = 'danger'; 
}

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $msg = 'Password berhasil direset. Silakan login.';
    $alert_type = 'success';
}

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Career Development Center Universitas Andalas</title>

  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/png" href="../foto/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="bg-blur">
    <div class="form">
        <form action="login_proses.php" method="post">
            <h2 class="mb-4">Login</h2>

            <?php if (!empty($msg)) : ?>
            <div class="alert alert-<?= $alert_type; ?>" role="alert">
                <?= htmlspecialchars($msg); ?>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <input type="email" name="email" class="form-control"
                       placeholder="Masukkan Email" required>
            </div>

            <div class="mb-3">
                <input type="password" name="password" class="form-control"
                       placeholder="Masukkan Password" required>
            </div>

            <button type="submit" name="submit" class="btn btn-primary w-100">
                Masuk Sekarang
            </button>

            <div class="mt-3 text-center">
                <a href="resetpassword.php" class="text-decoration-none">Lupa Password?</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
