<?php
include "koneksi.php";
include "navbar.php";

// LOGIKA FILTER WAKTU
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$whereAsesmen = "";
$whereKonseling = "";
$textFilter = "Semua Data";

$colTglAsesmen = "tanggal_asesmen"; 
$colTglKonseling = "jadwal_konseling"; 

switch ($filter) {
    case '3bulan':
        $whereAsesmen = "AND $colTglAsesmen >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        $whereKonseling = "AND $colTglKonseling >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        $textFilter = "3 Bulan Terakhir";
        break;
    case '6bulan':
        $whereAsesmen = "AND $colTglAsesmen >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        $whereKonseling = "AND $colTglKonseling >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        $textFilter = "6 Bulan Terakhir";
        break;
    case '1tahun':
        $whereAsesmen = "AND $colTglAsesmen >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $whereKonseling = "AND $colTglKonseling >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $textFilter = "1 Tahun Terakhir";
        break;
}

// QUERY KARTU STATISTIK (CARD)
$querymen = "
    SELECT COUNT(a.id_asesmen) as total 
    FROM asesmen a 
    LEFT JOIN konseling k ON a.id_asesmen = k.id_asesmen 
    WHERE k.id_asesmen IS NULL 
    $whereAsesmen
";

$q1 = $koneksi->query("SELECT COUNT(*) as total FROM asesmen WHERE 1=1 $whereAsesmen");
$totalAsesmen = $q1->fetch_assoc()['total'];

$q2 = $koneksi->query($querymen);
$totalPKonseling = $q2->fetch_assoc()['total'];

$q3 = $koneksi->query("SELECT COUNT(*) as total FROM konseling WHERE status='Disetujui' $whereKonseling");
$totalTJKonseling = $q3->fetch_assoc()['total'];

$q4 = $koneksi->query("SELECT COUNT(*) as total FROM konseling WHERE status='Selesai' $whereKonseling");
$totalSelesai = $q4->fetch_assoc()['total'];



// QUERY UNTUK GRAFIK (CHART)

function prepareChartData($result, $colLabel, $colTotal, $defaultLabel = 'Lainnya') {
    $labels = [];
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lbl = empty($row[$colLabel]) ? $defaultLabel : $row[$colLabel];
        $labels[] = $lbl;
        $data[] = $row[$colTotal];
    }
    return ['labels' => $labels, 'data' => $data];
}

// Grafik Kategori Permasalahan
$qMasalah = mysqli_query($koneksi, "
    SELECT kategori_permasalahan, COUNT(*) as jumlah 
    FROM asesmen 
    WHERE kategori_permasalahan != '' $whereAsesmen 
    GROUP BY kategori_permasalahan
");
$chartMasalah = prepareChartData($qMasalah, 'kategori_permasalahan', 'jumlah');

// Grafik COI / Minat Karier
$colCOI = 'hasil_asesmen'; 
$cekKolom = $koneksi->query("SHOW COLUMNS FROM asesmen LIKE '$colCOI'");
if($cekKolom->num_rows > 0) {
    $qCOI = mysqli_query($koneksi, "
        SELECT $colCOI, COUNT(*) as jumlah 
        FROM asesmen 
        WHERE $colCOI != '' $whereAsesmen 
        GROUP BY $colCOI ORDER BY jumlah DESC LIMIT 10
    ");
    $chartCOI = prepareChartData($qCOI, $colCOI, 'jumlah');
} else {
    $chartCOI = ['labels' => ['Data Kosong'], 'data' => [0]];
}

// Grafik Jenis Kelamin
$qJK = mysqli_query($koneksi, "SELECT jenis_kelamin, COUNT(*) as jumlah FROM mahasiswa GROUP BY jenis_kelamin");
$chartJK = prepareChartData($qJK, 'jenis_kelamin', 'jumlah');

// Grafik Fakultas
$qFakultas = mysqli_query($koneksi, "
    SELECT f.nama_fakultas AS fakultas, COUNT(*) AS jumlah
    FROM mahasiswa m
    JOIN jurusan j ON j.id_jurusan = m.id_jurusan
    JOIN fakultas f ON f.id_fakultas = j.id_fakultas
    GROUP BY f.id_fakultas, f.nama_fakultas
    ORDER BY jumlah DESC
");
$chartFakultas = prepareChartData($qFakultas, 'fakultas', 'jumlah');


// Grafik Angkatan
$qAngkatan = mysqli_query($koneksi, "SELECT angkatan, COUNT(*) as jumlah FROM mahasiswa GROUP BY angkatan ORDER BY angkatan ASC");
$chartAngkatan = prepareChartData($qAngkatan, 'angkatan', 'jumlah');

// Grafik Jurusan
$qJurusan = mysqli_query($koneksi, "
    SELECT j.nama_jurusan AS jurusan, COUNT(*) AS jumlah
    FROM mahasiswa m
    JOIN jurusan j ON j.id_jurusan = m.id_jurusan
    GROUP BY j.id_jurusan, j.nama_jurusan
    ORDER BY jumlah DESC
");
$chartJurusan = prepareChartData($qJurusan, 'jurusan', 'jumlah');

$colMasalah = "permasalahan";
$colTanggal = "tanggal_asesmen";

$sqlLatest = "
WITH ranked AS (
    SELECT 
        kategori_permasalahan,
        $colMasalah AS masalah,
        $colTanggal AS tanggal,
        ROW_NUMBER() OVER (
            PARTITION BY kategori_permasalahan
            ORDER BY $colTanggal DESC
        ) AS rn
    FROM asesmen
    WHERE kategori_permasalahan != ''
      AND TRIM($colMasalah) != ''
      $whereAsesmen
)
SELECT kategori_permasalahan, masalah, tanggal
FROM ranked
WHERE rn <= 5
ORDER BY kategori_permasalahan, tanggal DESC
";

$resLatest = mysqli_query($koneksi, $sqlLatest);

$latestMasalah = [];
while ($row = mysqli_fetch_assoc($resLatest)) {
    $cluster = $row['kategori_permasalahan'];
    if (!isset($latestMasalah[$cluster])) $latestMasalah[$cluster] = [];
    $latestMasalah[$cluster][] = $row;
}


?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard CDC - Universitas Andalas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card-header { background-color: #fff; border-bottom: 1px solid #e3e6f0; }
        .text-xs { font-size: .7rem; }
        .fw-bold { font-weight: 700!important; }
    </style>
</head>
<body class="bg-light">

<div id="content">
  <div class="container-fluid p-4">
    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Dashboard Monitoring</h1>
            <p class="mb-0 text-muted">Data Statistik: <strong><?= $textFilter ?></strong></p>
        </div>
        <form method="GET" action="" class="d-none d-sm-inline-block form-inline ml-auto shadow-sm">
            <div class="input-group">
                <select name="filter" class="form-select form-select-sm bg-white border-0" onchange="this.form.submit()" style="cursor:pointer;">
                    <option value="semua" <?= $filter == 'semua' ? 'selected' : '' ?>>Semua Waktu</option>
                    <option value="3bulan" <?= $filter == '3bulan' ? 'selected' : '' ?>>3 Bulan Terakhir</option>
                    <option value="6bulan" <?= $filter == '6bulan' ? 'selected' : '' ?>>6 Bulan Terakhir</option>
                    <option value="1tahun" <?= $filter == '1tahun' ? 'selected' : '' ?>>1 Tahun Terakhir</option>
                </select>
                <div class="input-group-append">
                    <button class="btn btn-primary btn-sm" type="button"><i class="fas fa-filter fa-sm"></i></button>
                </div>
            </div>
        </form>
    </div>

    <div class="row mb-4">
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-4 border-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Asesmen</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalAsesmen ?></div>
                            
                            <a href="asesmen.php" class="text-xs font-weight-bold text-primary text-decoration-none">
                                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                        <div class="col-auto"><i class="fas fa-clipboard-list fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-4 border-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Permintaan Masuk</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalPKonseling ?></div>
                            
                            <a href="asesmen.php?status=menunggu" class="text-xs font-weight-bold text-info text-decoration-none">
                                Lihat Permintaan <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                        <div class="col-auto"><i class="fas fa-comments fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-4 border-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Konseling Terjadwal</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalTJKonseling ?></div>

                            <a href="asesmen.php?status=Disetujui" class="text-xs font-weight-bold text-info text-decoration-none">
                                Lihat Jadwal <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                        <div class="col-auto"><i class="fas fa-calendar-check fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-4 border-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Konseling Selesai</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalSelesai ?></div>

                            <a href="asesmen.php?status=Selesai" class="text-xs font-weight-bold text-success text-decoration-none">
                                Lihat Riwayat <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                        <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Permasalahan Dominan</h6>
                    <button onclick="downloadChart('chartMasalah', 'Laporan_Masalah')" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></button>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="chartMasalah"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">COI / Minat Karier Dominan</h6>
                    <button onclick="downloadChart('chartCOI', 'Laporan_COI')" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></button>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="chartCOI"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
    <?php foreach ($latestMasalah as $cluster => $items): ?>
    <div class="col-lg-4 mb-4">
        <div class="card shadow h-100">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary">
            5 Permasalahan Terbaru
            </h6>
            <small class="text-muted">
            Cluster: <strong><?= htmlspecialchars($cluster) ?></strong>
            </small>
        </div>

        <div class="card-body">
            <ol class="ps-3">
            <?php foreach ($items as $it): ?>
                <li class="mb-2">
                <div class="small text-muted">
                    <?= date('d M Y', strtotime($it['tanggal'])) ?>
                </div>
                <div>
                    <?= htmlspecialchars($it['masalah']) ?>
                </div>
                </li>
            <?php endforeach; ?>
            </ol>
        </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Persebaran Departemen Mahasiswa</h6>
                    <button onclick="downloadChart('chartJurusan', 'Laporan_Jurusan')" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></button>
                </div>
                <div class="card-body">
                    <div style="height: 400px;"> <canvas id="chartJurusan"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Persebaran Angkatan</h6>
                    <button onclick="downloadChart('chartAngkatan', 'Laporan_Angkatan')" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></button>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="chartAngkatan"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Jenis Kelamin</h6>
                    <button onclick="downloadChart('chartJK', 'Laporan_JK')" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></button>
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chartJK"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Persebaran Fakultas</h6>
                    <button onclick="downloadChart('chartFakultas', 'Laporan_Fakultas')" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></button>
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chartFakultas"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const toggleBtn = document.getElementById("toggleSidebar");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    content.classList.toggle("shift");
  });
</script>

<script>
    Chart.defaults.font.family = 'Nunito, -apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.color = '#858796';
    const colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69', '#2c9faf', '#e15566'];

    // CHART MASALAH DOMINAN (Bar)
    new Chart(document.getElementById('chartMasalah'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartMasalah['labels']); ?>,
            datasets: [{
                label: 'Mahasiswa',
                data: <?php echo json_encode($chartMasalah['data']); ?>,
                backgroundColor: colors,
            }],
        },
        options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });

    // CHART COI / MINAT (Bar)
    new Chart(document.getElementById('chartCOI'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartCOI['labels']); ?>,
            datasets: [{
                label: 'Jumlah',
                data: <?php echo json_encode($chartCOI['data']); ?>,
                backgroundColor: '#f6c23e',
            }],
        },
        options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });

    // CHART JURUSAN (Bar Full Width)
    new Chart(document.getElementById('chartJurusan'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartJurusan['labels']); ?>,
            datasets: [{
                label: 'Mahasiswa',
                data: <?php echo json_encode($chartJurusan['data']); ?>,
                backgroundColor: '#1cc88a',
            }],
        },
        options: { 
            maintainAspectRatio: false, 
            scales: { 
                y: { beginAtZero: true },
                x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } }
            } 
        }
    });

    // CHART ANGKATAN (Line)
    new Chart(document.getElementById('chartAngkatan'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartAngkatan['labels']); ?>,
            datasets: [{
                label: 'Jumlah',
                data: <?php echo json_encode($chartAngkatan['data']); ?>,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                tension: 0.3,
                fill: true
            }],
        },
        options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    // CHART JENIS KELAMIN (Pie)
    new Chart(document.getElementById('chartJK'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chartJK['labels']); ?>,
            datasets: [{
                data: <?php echo json_encode($chartJK['data']); ?>,
                backgroundColor: ['#4e73df', '#e74a3b', '#36b9cc'], 
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: { maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
    });

    // CHART FAKULTAS (Bar Horizontal)
    new Chart(document.getElementById('chartFakultas'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartFakultas['labels']); ?>,
            datasets: [{
                label: 'Jumlah',
                data: <?php echo json_encode($chartFakultas['data']); ?>,
                backgroundColor: '#36b9cc',
            }],
        },
        options: { 
            indexAxis: 'y', // Bar Horizontal
            maintainAspectRatio: false,
        }
    });

    // Fungsi Download Gambar
    function downloadChart(canvasId, fileName) {
        var canvas = document.getElementById(canvasId);
        var newCanvas = document.createElement('canvas');
        newCanvas.width = canvas.width;
        newCanvas.height = canvas.height;
        var ctx = newCanvas.getContext('2d');
        ctx.fillStyle = '#FFFFFF'; 
        ctx.fillRect(0, 0, newCanvas.width, newCanvas.height);
        ctx.drawImage(canvas, 0, 0);
        var link = document.createElement('a');
        link.download = fileName + '.jpg';
        link.href = newCanvas.toDataURL('image/jpeg', 1.0);
        link.click();
    }

    
</script>
</body>
</html>