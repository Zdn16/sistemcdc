<?php
include "koneksi.php";
include "navbar.php";
?>

<?php
$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($koneksi, $_GET['keyword']) : "";

$query = "SELECT 
            j.id_jurusan,
            j.nama_jurusan,
            j.id_fakultas,
            f.nama_fakultas
          FROM jurusan j
          JOIN fakultas f ON f.id_fakultas = j.id_fakultas
          WHERE 1=1";

// search jurusan
if (!empty($keyword)) {
    $query .= " AND j.nama_jurusan LIKE '%$keyword%'";
}


$query .= " ORDER BY j.id_jurusan ASC";

$result = mysqli_query($koneksi, $query);
if (!$result) die("Query gagal: " . mysqli_error($koneksi));

$fakultasResult = mysqli_query($koneksi, "SELECT id_fakultas, nama_fakultas FROM fakultas ORDER BY nama_fakultas ASC");
$no=1;
?>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Career Development Center Universitas Andalas</title>
</head>


<div id="content">
  <div class="container">
    <div class="row">
      <div class="col-lg-12 mt-2">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Daftar Departemen</span>

                <a href="admin_fakultas.php" class="btn btn-primary">
                    Daftar Fakultas
                </a>
            </div>


          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <a href="admin_jurusan_tambah.php" class="btn btn-primary w-auto">
                Tambah Departemen
              </a>

              <form action="" method="GET" class="d-flex gap-2 mb-3">
                  <input type="text" class="form-control" name="keyword"
                        placeholder="Cari Departemen"
                        value="<?= $_GET['keyword'] ?? '' ?>">

                  <button type="submit" name="cari" class="btn btn-primary"> Cari </button>
                  <a class="btn btn-outline-secondary btn-sm" href="admin_jurusan.php">Reset</a>
              </form>
            </div>

            <div class="row mt-3">
              <div class="col">
                <table class="table table-bordered table-striped">
                    <tr>
                        <th>No</th>
                        <th>Fakultas</th>
                        <th>Departemen</th>
                        <th>Aksi</th>
                    </tr>

                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= $row['nama_fakultas']; ?></td>
                            <td><?= $row['nama_jurusan']; ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="admin_jurusan_hapus.php?id=<?= $row['id_jurusan']; ?>" 
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Yakin ingin menghapus data ini?')">
                                        Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                        

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                        <td colspan="15" class="text-center">Data tidak ditemukan</td>
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

<script>
  const toggleBtn = document.getElementById("toggleSidebar");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    content.classList.toggle("shift");
  });

document.querySelectorAll(".toggle-keterangan").forEach(btn => {
    btn.addEventListener("click", () => {
        const id = btn.dataset.id;
        const status = btn.dataset.status;

        // toggle status
        const newStatus = status == "1" ? "0" : "1";

        // AJAX request
       fetch("./coi_ket_update.php", {
            method: "POST",
            body: new URLSearchParams({
                id_item: id,
                keterangan: newStatus
            })
        })
        .then(response => response.text())
        .then(data => {
            data = data.trim(); // hapus whitespace
            if (data === "success") {
                btn.dataset.status = newStatus;
                btn.classList.toggle("btn-success");
                btn.classList.toggle("btn-secondary");
                btn.textContent = newStatus == 1 ? "Aktif" : "Nonaktif";
            } else {
                alert("Gagal update keterangan: " + data);
            }
        })
        .catch(err => alert("Error: " + err));

    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
