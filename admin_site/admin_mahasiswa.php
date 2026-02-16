<?php
session_start();
include "koneksi.php";
include "navbar.php"; 

// Update Status
if (isset($_POST['update_status'])) {
    $nim_edit    = mysqli_real_escape_string($koneksi, $_POST['nim']);
    $status_baru = mysqli_real_escape_string($koneksi, $_POST['status_mahasiswa']);

    $update = mysqli_query($koneksi, "UPDATE mahasiswa SET status_mahasiswa = '$status_baru' WHERE nim = '$nim_edit'");
    
    if ($update) {
        echo "<script>
            alert('Status mahasiswa berhasil diubah!'); 
            window.location='admin_mahasiswa.php';
        </script>";
    } else {
        echo "<script>alert('Gagal mengubah status.');</script>";
    }
}

// Hapus Data
if (isset($_GET['hapus'])) {
    $nim_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    $q_foto = mysqli_query($koneksi, "SELECT foto FROM mahasiswa WHERE nim='$nim_hapus'");
    $d_foto = mysqli_fetch_assoc($q_foto);
    if(!empty($d_foto['foto']) && file_exists("uploads/".$d_foto['foto'])){
        unlink("uploads/".$d_foto['foto']);
    }
    
    mysqli_query($koneksi, "DELETE FROM mahasiswa WHERE nim='$nim_hapus'");
    echo "<script>alert('Data terhapus'); window.location='admin_mahasiswa.php';</script>";
}

// Query Pencarian
$keyword = "";
$where_clause = "";

if (isset($_GET['cari'])) {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['keyword']);
    $where_clause = "WHERE m.nama LIKE '%$keyword%' OR m.nim LIKE '%$keyword%'";
}


$query = "SELECT m.*, j.nama_jurusan 
          FROM mahasiswa m 
          LEFT JOIN jurusan j ON m.id_jurusan = j.id_jurusan 
          $where_clause 
          ORDER BY m.angkatan DESC, m.nama ASC";

$result = mysqli_query($koneksi, $query);
$no = 1;
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Data Mahasiswa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
      .img-profil-mini { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; }
      .table-hover tbody tr:hover { background-color: #f8f9fa; }
  </style>
</head>

<body class="bg-light">

<div id="content">
  <div class="container py-4">
    <div class="row">
      <div class="col-lg-12">
        
        <div class="card shadow-sm border-0 rounded-3">
          
           <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Data Mahasiswa</h5>

                <form action="" method="GET" class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" name="keyword" 
                        value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari Nama / NIM...">
                    
                    <button type="submit" name="cari" class="btn btn-primary btn-sm">Cari</button>
                    
                    <?php if($keyword): ?>
                        <a href="admin_mahasiswa.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>No</th>
                    <th>Mahasiswa</th> <th>NIM</th>
                    <th>Jurusan</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (mysqli_num_rows($result) > 0): ?>
                      <?php while ($row = mysqli_fetch_assoc($result)): ?>
                      <tr>
                          <td><?= $no++; ?></td>
                          
                          <td>
                            <div class="d-flex align-items-center">
                                <?php 
                                    $folder_upload = "../mahasiswa_site/uploads/"; 
                                    $path_file = $folder_upload . $row['foto'];
                                    if (!empty($row['foto']) && file_exists($path_file)) {
                                        $foto_profil = $path_file;
                                    } else {
                                        $foto_profil = "https://ui-avatars.com/api/?name=" . urlencode($row['nama']) . "&background=random&size=128";
                                    }
                                ?>
                                
                                <img src="<?= $foto_profil ?>" class="img-profil-mini me-2" 
                                    style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                                
                                <div>
                                    <span class="fw-bold d-block"><?= htmlspecialchars($row['nama']); ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($row['email']); ?></small>
                                </div>
                            </div>
                        </td>

                          <td><?= htmlspecialchars($row['nim']); ?></td>
                          
                          <td>
                              <?= htmlspecialchars($row['nama_jurusan']); ?><br>
                              <span class="badge bg-light text-dark border">Angkatan <?= $row['angkatan']; ?></span>
                          </td>

                          <td>
                              <?php 
                                $bg_status = ($row['status_mahasiswa'] == 'Aktif') ? 'bg-success' : 'bg-secondary';
                              ?>
                              <span class="badge <?= $bg_status ?> rounded-pill">
                                <?= htmlspecialchars($row['status_mahasiswa']); ?>
                              </span>
                          </td>

                          <td class="text-end">
                              <button type="button" class="btn btn-warning btn-sm text-white" 
                                      data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['nim']; ?>" title="Ubah Status">
                                  <i class="fas fa-edit"></i>
                              </button>

                              <a href="?hapus=<?= $row['nim']; ?>" class="btn btn-danger btn-sm" 
                                 onclick="return confirm('Hapus data mahasiswa <?= $row['nama']; ?>?');" title="Hapus">
                                  <i class="fas fa-trash"></i>
                              </a>
                          </td>
                      </tr>

                      <div class="modal fade" id="modalEdit<?= $row['nim']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-sm modal-dialog-centered">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h6 class="modal-title fw-bold">Update Status</h6>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <p class="small text-muted mb-2">Ubah status untuk <strong><?= $row['nama']; ?></strong></p>
                                    <input type="hidden" name="nim" value="<?= $row['nim']; ?>">
                                    
                                    <select name="status_mahasiswa" class="form-select">
                                        <option value="Aktif" <?= ($row['status_mahasiswa'] == 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="Alumni" <?= ($row['status_mahasiswa'] == 'Alumni') ? 'selected' : ''; ?>>Alumni</option>
                                    </select>
                                </div>
                                <div class="modal-footer p-1">
                                    <button type="submit" name="update_status" class="btn btn-primary btn-sm w-100">Simpan</button>
                                </div>
                            </form>
                          </div>
                        </div>
                      </div>
                      <?php endwhile; ?>
                  <?php else: ?>
                      <tr>
                          <td colspan="6" class="text-center py-4 text-muted">Data mahasiswa tidak ditemukan.</td>
                      </tr>
                  <?php endif; ?>
                </tbody>
              </table>
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