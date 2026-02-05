<?php
include "koneksi.php";
include "navbar.php";
?>

<?php
$keyword = isset($_GET['keyword']) 
            ? mysqli_real_escape_string($koneksi, $_GET['keyword']) 
            : "";

$filter = $_GET['filter'] ?? "";

$query = "SELECT * FROM item_pertanyaan WHERE 1=1";

// =======================
// SEARCH KEYWORD
// =======================
if (!empty($keyword)) {
    $query .= " AND (pertanyaan LIKE '%$keyword%' 
                     OR kategori LIKE '%$keyword%')";
}

// =======================
// FILTER CAREER ANCHOR
// =======================
if (!empty($filter)) {
    $query .= " AND kategori = '$filter'";
}

// =======================
// SORT DEFAULT
// =======================
$query .= " ORDER BY id_item ASC";

$result = mysqli_query($koneksi, $query);
$no = 1;

if (!$result) {
    die("Query gagal: " . mysqli_error($koneksi));
}

$kategoriLabel = [
    'autonomy'  => 'Autonomy/Independence',
    'security'  => 'Security/Stability',
    'tf'        => 'Technical Function',
    'gm'        => 'General Managerial Competence',
    'ec'        => 'Entrepreneurial Creativity',
    'service'   => 'Service Dedication to a Cause',
    'challenge' => 'Pure Challenge',
    'lifestyle' => 'Lifestyle'
];
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
            Pertanyaan Career Anchor
          </div>

          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <a href="coi_tambah.php" class="btn btn-primary w-auto">
                Tambah Pertanyaan
              </a>

              <form action="" method="GET" class="d-flex gap-2 mb-3">
                  <input type="text" class="form-control" name="keyword"
                        placeholder="Cari pertanyaan / kategori"
                        value="<?= $_GET['keyword'] ?? '' ?>">

                  <select name="filter" class="form-select">
                      <option value="">Semua Career Anchor</option>
                      <option value="autonomy"  <?= ($_GET['filter'] ?? '')=='autonomy' ? 'selected' : '' ?>>Autonomy / Independence</option>
                      <option value="security"  <?= ($_GET['filter'] ?? '')=='security' ? 'selected' : '' ?>>Security / Stability</option>
                      <option value="tf"        <?= ($_GET['filter'] ?? '')=='tf' ? 'selected' : '' ?>>Technical Function</option>
                      <option value="gm"        <?= ($_GET['filter'] ?? '')=='gm' ? 'selected' : '' ?>>General Managerial</option>
                      <option value="ec"        <?= ($_GET['filter'] ?? '')=='ec' ? 'selected' : '' ?>>Entrepreneurial Creativity</option>
                      <option value="service"   <?= ($_GET['filter'] ?? '')=='service' ? 'selected' : '' ?>>Service / Dedication</option>
                      <option value="challenge" <?= ($_GET['filter'] ?? '')=='challenge' ? 'selected' : '' ?>>Pure Challenge</option>
                      <option value="lifestyle" <?= ($_GET['filter'] ?? '')=='lifestyle' ? 'selected' : '' ?>>Lifestyle</option>
                  </select>

                  <button type="submit" name="cari" class="btn btn-primary">
                      Terapkan
                  </button>
              </form>
            </div>

            <div class="row mt-3">
              <div class="col">
                <table class="table table-bordered table-striped">
                    <tr>
                        <th>No</th>
                        <th>Pertanyaan</th>
                        <th>Kategori</th>
                        <th>Aksi</th>
                    </tr>

                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= $row['pertanyaan']; ?></td>                        
                            <td><?= $kategoriLabel[$row['kategori']] ?? strtoupper($row['kategori']); ?></td>
                            <td>
                                <a href="coi_edit.php?id=<?= $row['id_item']; ?>" class="btn btn-warning btn-sm">
                                    Edit
                                </a>
                        </tr>
                        
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                        <td colspan="5" class="text-center">Data tidak ditemukan</td>
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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
