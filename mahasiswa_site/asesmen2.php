<?php
session_start();

$error = $_SESSION['error_permasalahan'] ?? '';
$old   = $_SESSION['old_permasalahan'] ?? '';
unset($_SESSION['error_permasalahan'], $_SESSION['old_permasalahan']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Data Diri | CDC Universitas Andalas</title>

  <link rel="stylesheet" href="style_asesmen.css">
  <link rel="icon" type="image/png" href="../foto/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="bg-blur d-flex min-vh-100 justify-content-center align-items-center">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <div class="card shadow rounded-4">
          <div class="card-body p-5">

            <h3 class="fw-bold mb-2">Permasalahan dan Asesmen Karier</h3>
            <p class="text-muted mb-4">Career Development Center Universitas Andalas</p>

            <!-- ALERT  -->
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
              </div>
            <?php endif; ?>

            <form action="proses_asesmen2.php" method="post">

              <!-- Permasalahan -->
              <div class="mb-3">
                <label for="permasalahan" class="form-label">Permasalahan</label>
                <textarea
                  id="permasalahan"
                  name="permasalahan"
                  class="form-control"
                  rows="5"
                  placeholder="Ceritakan permasalahan kamu"
                  required
                ><?= htmlspecialchars($old) ?></textarea>
              </div>

              <!-- Tombol -->
              <div class="d-flex justify-content-between mt-4">
                <a href="home_mahasiswa.php" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-primary">Lanjut</button>
              </div>

            </form>

          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
