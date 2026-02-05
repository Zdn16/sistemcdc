<?php
include "koneksi.php";
include "navbar.php";
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
            Data Asesmen dan Permintaan Konseling
          </div>

          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <a href="detail_permasalahan.php" class="btn btn-primary w-auto">
                Detail Permasalahan
              </a>
            <!-- Fungsi Cari dan FIlter -->
            <div class="d-flex justify-content-end mb-3">
                <form action="" method="GET" class="d-flex gap-2">
                    <input type="text" class="form-control" name="keyword"
                          placeholder="Cari Nama / NIM"
                          value="<?= $_GET['keyword'] ?? '' ?>">
                          
                    <select name="filter" class="form-select">
                        <option value="">Semua Data</option>
                        <option value="az" <?= ($_GET['filter'] ?? '')=='az' ? 'selected' : '' ?>>Nama A–Z</option>
                        <option value="menunggu" <?= ($_GET['filter'] ?? '')=='menunggu' ? 'selected' : '' ?>>Status Menunggu</option>
                        <option value="disetujui" <?= ($_GET['filter'] ?? '')=='disetujui' ? 'selected' : '' ?>>Status Disetujui</option>
                        <option value="selesai" <?= ($_GET['filter'] ?? '')=='selesai' ? 'selected' : '' ?>>Status Selesai</option>
                        <option value="lanjutan" <?= ($_GET['filter'] ?? '')=='lanjutan' ? 'selected' : '' ?>>Status Lanjutan</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Terapkan</button>
                    <a class="btn btn-outline-secondary btn-sm" href="asesmen.php">Reset</a>
                </form>
            </div>
            </div>

            <table class="table table-bordered table-striped">
                <tr>
                  <th>No</th>
                  <th>Nama</th>
                  <th>NIM</th>
                  <th>Permasalahan</th>
                  <th>Nama Konselor</th>
                  <th>Jadwal Konseling</th>
                  <th>Lokasi</th>
                  <th>Status</th>
                  <th>Sesi</th>
                  <th>Aksi</th>
                  <th>Lihat</th>
              </tr>

<?php
$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($koneksi, $_GET['keyword']) : "";

// Cek apakah ada input 'filter' (dari dropdown)
if (isset($_GET['filter']) && $_GET['filter'] != '') {
    $filter = $_GET['filter'];
} 
// Jika tidak ada filter, cek apakah ada input 'status' 
elseif (isset($_GET['status']) && $_GET['status'] != '') {
    // Ubah jadi huruf kecil 
    $filter = strtolower($_GET['status']); 
} 
// Default kosong
else {
    $filter = 'baru';
}

$sql = "
SELECT a.id_asesmen, m.nama, m.nim, a.permasalahan,
       k.id_konseling, k.jadwal_konseling, k.lokasi, k.status, k.sesi,
       u.nama_user AS nama_konselor
FROM asesmen a
JOIN mahasiswa m ON a.nim = m.nim
LEFT JOIN konseling k ON a.id_asesmen = k.id_asesmen
LEFT JOIN user u ON k.id_user = u.id_user
WHERE 1=1";

// tambahkan filter keyword jika ada
if(!empty($keyword)){
    $sql .= " AND (m.nama LIKE '%$keyword%' OR m.nim LIKE '%$keyword%')";
}

switch ($filter) {
    case 'az':
        $sql .= " ORDER BY m.nama ASC";
        break;

    case 'jadwal_kosong':
        $sql .= " AND k.id_konseling IS NULL 
              ORDER BY a.tanggal_asesmen DESC";
        break;
        
    case 'baru':
        $sql .= " ORDER BY a.tanggal_asesmen DESC";
        break;

    case 'menunggu':
        // Logika: Ambil yang statusnya tertulis 'Menunggu' ATAU yang statusnya masih Kosong (NULL)
        $sql .= " AND (k.status = 'Menunggu' OR k.status IS NULL OR k.status = '')
                  ORDER BY a.tanggal_asesmen DESC";
        break;
    // -------------------------

    case 'disetujui':
        $sql .= " AND k.status = 'Disetujui'
                  ORDER BY a.tanggal_asesmen DESC";
        break;

    case 'selesai':
        $sql .= " AND k.status = 'Selesai'
                  ORDER BY a.tanggal_asesmen DESC";
        break;
    
    case 'lanjutan':
        $sql .= " AND k.status = 'Lanjutan'
                  ORDER BY a.tanggal_asesmen DESC";
        break;

    default:
        $sql .= " ORDER BY a.tanggal_asesmen DESC"; 
}

$result = mysqli_query($koneksi, $sql);

// cek query
if(!$result){
    die("Query gagal: " . mysqli_error($koneksi));
}


if(mysqli_num_rows($result) > 0){
    $no = 1;
    while($row = mysqli_fetch_assoc($result)){

        $jadwalRaw = $row['jadwal_konseling'];
        
        // Cek apakah jadwal ada dan tidak kosong/nol
        if (!empty($jadwalRaw) && $jadwalRaw != '0000-00-00 00:00:00') {
            // Ubah format jadi: 13-01-2026 14:30 WIB
            $jamDisplay = date('d-m-Y H:i', strtotime($jadwalRaw)) . ' WIB';
        } else {
            $jamDisplay = '<span>-</span>';
        }

        echo "<tr>
                <td>{$no}</td>
                <td>{$row['nama']}</td>
                <td>{$row['nim']}</td>
                <td>{$row['permasalahan']}</td>
                <td>".($row['nama_konselor'] ?? '-')."</td>
                <td>{$jamDisplay}</td>
                <td>".($row['lokasi'] ?? '-'). "</td>
                <td>".($row['status'] ?? 'Menunggu')."</td>
                <td>".($row['sesi'] ?? '-')."</td>
                <td>
                    <a href='asesmen_inputkonseling.php?id_asesmen={$row['id_asesmen']}' class='btn btn-primary btn-sm'>Input</a>
                </td>
                <td>
                    <a href='admin_lihatdetail.php?id_asesmen={$row['id_asesmen']}' class='btn btn-primary btn-sm'>Lihat Detail</a>
                </td>
                <td>";
        

        echo "</td></tr>";
        $no++;
    }
} else {
    echo "<tr><td colspan='9' class='text-center'>Data tidak ditemukan</td></tr>";
}

?>
            </table>

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
