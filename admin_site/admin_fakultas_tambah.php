<?php
    include "koneksi.php";
    include "navbar.php";
?>

<?php if (isset($_GET['error']) && $_GET['error'] == 'duplicate'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Gagal Menambahkan!</strong> Nama Fakultas tersebut sudah ada di database.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Fakultas</title>
</head>

<div id="content">
<div class="container">
    <div class="row mt-3">
        <div class="col-lg-12 mt-2" style="min-height: 480px;">
            <div class="card">
                <div class="card-header">
                    Tambah Fakultas
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col">

                            <form action="admin_fakultas_simpan.php" method="POST">

                            <div class="mb-3">
                                <label for="nama_fakultas" class="form-label">Nama Fakultas</label>
                                <input type="text" class="form-control" id="nama_fakultas" name="nama_fakultas" placeholder="Masukkan Nama Fakultas" required>
                            </div>

                            <div class="mb-3">
                                <label for="nama_fakultas" class="form-label">Singkatan Fakultas</label>
                                <input type="text" class="form-control" id="singkatan" name="singkatan" placeholder="Masukkan Singkatan Fakultas" required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Tambah Fakultas
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


