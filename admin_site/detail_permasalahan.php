<?php
include "koneksi.php";
include "navbar.php";

$colMasalah  = "permasalahan";        // kolom teks masalah di tabel asesmen
$colCluster  = "kategori_permasalahan"; // kolom cluster
$colTanggal  = "tanggal_asesmen";     // kolom tanggal

// FILTER WAKTU
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$whereAsesmen = "";
$textFilter = "Semua Data";

switch ($filter) {
  case '3bulan':
    $whereAsesmen = "AND $colTanggal >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
    $textFilter = "3 Bulan Terakhir";
    break;
  case '6bulan':
    $whereAsesmen = "AND $colTanggal >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
    $textFilter = "6 Bulan Terakhir";
    break;
  case '1tahun':
    $whereAsesmen = "AND $colTanggal >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    $textFilter = "1 Tahun Terakhir";
    break;
  default:
    $textFilter = "Semua Data";
    break;
}

// FILTER CLUSTER & SEARCH
$cluster = isset($_GET['cluster']) ? trim($_GET['cluster']) : 'semua';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// PAGINATION
$perPage = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// AMBIL LIST CLUSTER YANG ADA (untuk tab)
$listCluster = [];
$resC = mysqli_query($koneksi, "
  SELECT DISTINCT $colCluster AS cluster
  FROM asesmen
  WHERE $colCluster != '' $whereAsesmen
  ORDER BY cluster ASC
");
while($row = mysqli_fetch_assoc($resC)){
  $listCluster[] = $row['cluster'];
}

// BUILD WHERE + PARAMS (prepared statement)
$where = "WHERE $colCluster != '' AND TRIM($colMasalah) != '' $whereAsesmen";
$types = "";
$params = [];

if ($cluster !== 'semua') {
  $where .= " AND $colCluster = ?";
  $types .= "s";
  $params[] = $cluster;
}

if ($q !== '') {
  $where .= " AND $colMasalah LIKE ?";
  $types .= "s";
  $params[] = "%$q%";
}

// HITUNG TOTAL DATA
$sqlCount = "SELECT COUNT(*) AS total FROM asesmen $where";
$stmtCount = $koneksi->prepare($sqlCount);
if($types !== ""){
  $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$totalRows = $stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();

$totalPages = max(1, (int)ceil($totalRows / $perPage));

// AMBIL DATA
$sqlData = "
  SELECT 
    id_asesmen,
    $colCluster AS cluster,
    $colMasalah AS masalah,
    $colTanggal AS tanggal
  FROM asesmen
  $where
  ORDER BY $colTanggal DESC
  LIMIT ? OFFSET ?
";

$stmt = $koneksi->prepare($sqlData);

// tambah limit offset ke params
$typesData = $types . "ii";
$paramsData = $params;
$paramsData[] = $perPage;
$paramsData[] = $offset;

$stmt->bind_param($typesData, ...$paramsData);
$stmt->execute();
$resData = $stmt->get_result();

$data = [];
while($row = $resData->fetch_assoc()){
  $data[] = $row;
}
$stmt->close();

// HELPER: build query string untuk pagination/tab
function buildQuery($overrides = []) {
  $base = $_GET;
  foreach($overrides as $k => $v){
    $base[$k] = $v;
  }
  return http_build_query($base);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar Permasalahan per Cluster</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div id="content">
<div class="container-fluid p-4">

  <div class="d-sm-flex align-items-center justify-content-between mb-3">
    <div>
      <h1 class="h4 mb-0">Permasalahan Mahasiswa per Cluster</h1>
      <div class="text-muted small">Filter: <strong><?= htmlspecialchars($textFilter) ?></strong> • Total data: <strong><?= (int)$totalRows ?></strong></div>
    </div>

    <form method="GET" class="d-flex gap-2">
      <input type="hidden" name="cluster" value="<?= htmlspecialchars($cluster) ?>">
      <input type="hidden" name="page" value="1">

      <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="semua" <?= $filter=='semua'?'selected':'' ?>>Semua Waktu</option>
        <option value="3bulan" <?= $filter=='3bulan'?'selected':'' ?>>3 Bulan Terakhir</option>
        <option value="6bulan" <?= $filter=='6bulan'?'selected':'' ?>>6 Bulan Terakhir</option>
        <option value="1tahun" <?= $filter=='1tahun'?'selected':'' ?>>1 Tahun Terakhir</option>
      </select>

      <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari keyword..." value="<?= htmlspecialchars($q) ?>">
      <button class="btn btn-primary btn-sm">Cari</button>
      <a class="btn btn-outline-secondary btn-sm" href="detail_permasalahan.php">Reset</a>
    </form>
  </div>

  <!-- Tabs Cluster -->
  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <a class="nav-link <?= $cluster==='semua'?'active':'' ?>"
         href="?<?= buildQuery(['cluster'=>'semua','page'=>1]) ?>">
        Semua
      </a>
    </li>

    <?php foreach($listCluster as $c): ?>
      <li class="nav-item">
        <a class="nav-link <?= ($cluster===$c)?'active':'' ?>"
           href="?<?= buildQuery(['cluster'=>$c,'page'=>1]) ?>">
          <?= htmlspecialchars($c) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <!-- Table -->
  <div class="card shadow-sm">
    <div class="card-body">
      <?php if(empty($data)): ?>
        <div class="alert alert-warning mb-0">Data tidak ditemukan untuk filter yang dipilih.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:80px;">ID</th>
                <th style="width:220px;">Cluster</th>
                <th>Permasalahan</th>
                <th style="width:160px;">Tanggal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($data as $row): ?>
                <tr>
                  <td><?= (int)$row['id_asesmen'] ?></td>
                  <td>
                    <span class="badge bg-primary"><?= htmlspecialchars($row['cluster']) ?></span>
                  </td>
                  <td><?= nl2br(htmlspecialchars($row['masalah'])) ?></td>
                  <td class="text-muted">
                    <?= $row['tanggal'] ? date('d M Y H:i', strtotime($row['tanggal'])) : '-' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center">
          <div class="small text-muted">
            Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong>
          </div>

          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item <?= $page<=1?'disabled':'' ?>">
                <a class="page-link" href="?<?= buildQuery(['page'=>$page-1]) ?>">&laquo;</a>
              </li>

              <?php
              // tampilkan range halaman yang "rapi"
              $start = max(1, $page - 2);
              $end   = min($totalPages, $page + 2);
              for($p=$start; $p<=$end; $p++):
              ?>
                <li class="page-item <?= $p==$page?'active':'' ?>">
                  <a class="page-link" href="?<?= buildQuery(['page'=>$p]) ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>

              <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                <a class="page-link" href="?<?= buildQuery(['page'=>$page+1]) ?>">&raquo;</a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
