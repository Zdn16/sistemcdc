<?php
    include "koneksi.php";
    include "navbar.php";
?>

<?php if (isset($_GET['error']) && $_GET['error'] == 'duplicate'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Gagal Menambahkan!</strong> Jurusan tersebut sudah terdaftar pada Fakultas yang Anda pilih.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
$fakultasResult = mysqli_query($koneksi, "SELECT id_fakultas, nama_fakultas, singkatan FROM fakultas ORDER BY nama_fakultas ASC");
if(!$fakultasResult){
    die("Gagal ambil data fakultas: " . mysqli_error($koneksi));
}
?>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Departemen</title>
</head>

<div id="content">
<div class="container">
    <div class="row mt-3">
        <div class="col-lg-12 mt-2" style="min-height: 480px;">
            <div class="card">
                <div class="card-header">
                    Tambah Departemen
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col">

                            <form action="admin_jurusan_simpan.php" method="POST">

                            <div class="mb-3">
                                <label for="nama_fakultas" class="form-label">Nama Departemen</label>
                                <input type="text" class="form-control" id="nama_jurusan" name="nama_jurusan" placeholder="Masukkan Nama Jurusan" required>
                            </div>

                            <div class="mb-3">
                                <label for="id_fakultas" class="form-label">Fakultas</label>
                                <select class="form-select" id="id_fakultas" name="id_fakultas" required>
                                    <option value="">-- Pilih Fakultas --</option>

                                    <?php while($f = mysqli_fetch_assoc($fakultasResult)): ?>
                                        <option value="<?= $f['id_fakultas']; ?>">
                                            <?= $f['nama_fakultas']; ?><?= isset($f['singkatan']) ? " ({$f['singkatan']})" : "" ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Tambah Departemen
                            </button>

                            </form>
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


