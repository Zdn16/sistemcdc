<?php
include "koneksi.php";
include "navbar.php";
?>

<?php
$keyword = isset($_GET['keyword']) 
            ? mysqli_real_escape_string($koneksi, $_GET['keyword']) 
            : "";

// =======================
// QUERY DASAR (UBAH DISINI: Tambahkan JOIN)
// =======================
// Kita ambil data profil_pekerjaan (p) dan nama_jurusan (j)
$query = "SELECT p.*, j.nama_jurusan 
          FROM profil_pekerjaan p
          LEFT JOIN jurusan j ON p.id_jurusan = j.id_jurusan
          WHERE 1=1";

// =======================
// SEARCH KEYWORD (UBAH DISINI)
// =======================
if (!empty($keyword)) {
    // Cari berdasarkan nama pekerjaan ATAU nama jurusan (dari tabel j)
    $query .= " AND (p.nama_pekerjaan LIKE '%$keyword%' 
                     OR j.nama_jurusan LIKE '%$keyword%')";
}

// =======================
// SORT DEFAULT
// =======================
$query .= " ORDER BY p.id_pekerjaan ASC";

$result = mysqli_query($koneksi, $query);
$no = 1;

if (!$result) {
    die("Query gagal: " . mysqli_error($koneksi));
}

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
          <div class="card-header">
            Profil Pekerjaan
          </div>

          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <a href="admin_pekerjaan_tambah.php" class="btn btn-primary w-auto">
                Tambah Pekerjaan
              </a>

              <form action="" method="GET" class="d-flex gap-2 mb-3">
                  <input type="text" class="form-control" name="keyword"
                        placeholder="Cari Pekerjaan/Departemen"
                        value="<?= $_GET['keyword'] ?? '' ?>">

                  <button type="submit" name="cari" class="btn btn-primary">
                      Terapkan
                  </button>
                  <a class="btn btn-outline-secondary btn-sm" href="admin_pekerjaan.php">Reset</a>
              </form>
            </div>

            <div class="row mt-3">
              <div class="col">
                <table class="table table-bordered table-striped">
                    <tr>
                        <th>No</th>
                        <th>Pekerjaan</th>
                        <th>Keterangan</th>
                        <th>Departemen</th>
                        <th>Autonomy</th>
                        <th>Security</th>
                        <th>TF</th>
                        <th>GM</th>
                        <th>EC</th>
                        <th>Service</th>
                        <th>Challenge</th>
                        <th>Lifestyle</th>
                        <th>Aksi</th>
                    </tr>

                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= $row['nama_pekerjaan']; ?></td>
                            <td>
                              <?php
                                $full = $row['ket_pekerjaan'] ?? '';
                                $fullEsc = htmlspecialchars($full, ENT_QUOTES, 'UTF-8');

                                // Pecah jadi kata (unicode safe)
                                $words = preg_split('/\s+/u', trim($full), -1, PREG_SPLIT_NO_EMPTY);

                                $limit = 10;
                                $short = $full;
                                $needToggle = false;

                                if (count($words) > $limit) {
                                    $needToggle = true;
                                    $short = implode(' ', array_slice($words, 0, $limit)) . '...';
                                }

                                $shortEsc = htmlspecialchars($short, ENT_QUOTES, 'UTF-8');
                                $id = (int)$row['id_pekerjaan'];
                              ?>

                              <span class="ket-short" id="ket-short-<?= $id ?>"><?= $shortEsc ?></span>
                              <span class="ket-full d-none" id="ket-full-<?= $id ?>"><?= $fullEsc ?></span>

                              <?php if ($needToggle): ?>
                                <a href="javascript:void(0)" class="ket-toggle ms-1" data-id="<?= $id ?>">selengkapnya</a>
                              <?php endif; ?>
                            </td>
                            <td><?= $row['nama_jurusan']; ?></td>                          
                            <td><?= $row['pk_autonomy']; ?></td>
                            <td><?= $row['pk_security']; ?></td>
                            <td><?= $row['pk_tf']; ?></td>
                            <td><?= $row['pk_gm']; ?></td>
                            <td><?= $row['pk_ec']; ?></td>
                            <td><?= $row['pk_service']; ?></td>
                            <td><?= $row['pk_challenge']; ?></td>
                            <td><?= $row['pk_lifestyle']; ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="admin_pekerjaan_edit.php?id=<?= $row['id_pekerjaan']; ?>" 
                                    class="btn btn-warning btn-sm">
                                        Edit
                                    </a>

                                    <a href="admin_pekerjaan_hapus.php?id=<?= $row['id_pekerjaan']; ?>" 
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

  if(toggleBtn){
      toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("active");
        content.classList.toggle("shift");
      });
  }

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

<script>
document.querySelectorAll(".ket-toggle").forEach(link => {
  link.addEventListener("click", () => {
    const id = link.dataset.id;

    const shortEl = document.getElementById("ket-short-" + id);
    const fullEl  = document.getElementById("ket-full-" + id);

    const isShowingFull = !fullEl.classList.contains("d-none");

    if (isShowingFull) {
      fullEl.classList.add("d-none");
      shortEl.classList.remove("d-none");
      link.textContent = "selengkapnya";
    } else {
      shortEl.classList.add("d-none");
      fullEl.classList.remove("d-none");
      link.textContent = "tutup";
    }
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>