<?php
include "koneksi.php";
include "navbar.php";

$keyword = "";
if (isset($_GET['cari'])) {
    $keyword = $_GET['keyword'];
    // Saya tambahkan pencarian berdasarkan status juga
    $query = "SELECT * FROM user 
              WHERE id_user LIKE '%$keyword%' 
              OR nama_user LIKE '%$keyword%'";
} else {
    $query = "SELECT * FROM user";
}

$result = mysqli_query($koneksi, $query);
$no = 1;
?>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Data Konselor dan Admin SKU</title>
</head>


<div id="content">
  <div class="container">
    <div class="row">
      <div class="col-lg-12 mt-2">
        <div class="card">
          <div class="card-header">
            Data Admin dan Konselor SKU
          </div>

          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <a href="user_tambah.php" class="btn btn-primary w-auto">
                Tambah Konselor
              </a>

              <form action="" class="d-flex align-items-center gap-2" method="GET">
                <input type="text" class="form-control me-2" name="keyword" value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>" placeholder="Cari Nama/ID">
                <input type="submit" class="btn btn-primary w-auto" name="cari" value="Cari">
                <a class="btn btn-outline-secondary btn-sm" href="user.php">Reset</a>
              </form>
            </div>

            <div class="row mt-3">
              <div class="col">
                <table class="table table-bordered table-striped">
                  <tr>
                      <th>No</th>
                      <th>ID Pengguna</th>
                      <th>Nama</th>
                      <th>Jenis Kelamin</th>
                      <th>Email</th>
                      <th>Role</th>
                      <th>Status</th>
                      <th>Aksi</th>
                  </tr>

                  <?php if (mysqli_num_rows($result) > 0): ?>
                      <?php while ($row = mysqli_fetch_assoc($result)): ?>
                      <tr>
                          <td><?= $no++; ?></td>
                          <td><?= $row['id_user']; ?></td>
                          <td><?= $row['nama_user']; ?></td>
                          <td><?= $row['jk_user']; ?></td>
                          <td><?= $row['email_user']; ?></td>
                          <td>
                              <?php if($row['role'] == 'admin'): ?>
                                  <span class="fw-bold text-primary">Admin</span>
                              <?php else: ?>
                                  <?= $row['role']; ?>
                              <?php endif; ?>
                          </td>
                          
                          <td class="text-center">
                              <?php if ($row['status'] == 1): ?>
                                  <span class="badge bg-success">Aktif</span>
                              <?php else: ?>
                                  <span class="badge bg-danger">Nonaktif</span>
                              <?php endif; ?>
                          </td>

                          <td class="text-center">
                              <?php if ($row['role'] === 'admin'): ?>
                                  <button class="btn btn-secondary btn-sm" disabled style="cursor: not-allowed; opacity: 0.6;">
                                      Locked
                                  </button>
                              <?php else: ?>
                                  <a href="user_edit.php?id=<?= $row['id_user']; ?>" class="btn btn-warning btn-sm">
                                      Edit Status
                                  </a>
                              <?php endif; ?>
                          </td>
                      </tr>
                      <?php endwhile; ?>
                  <?php else: ?>
                      <tr>
                          <td colspan="8" class="text-center">Data tidak ditemukan</td>
                      </tr>
                  <?php endif; ?>
              </table>

              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const toggleBtn = document.getElementById("toggleSidebar");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    content.classList.toggle("shift");
  });
</script>

</body>
</html>