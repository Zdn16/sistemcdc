<?php
session_start();
include "koneksi.php";
include "konselor_navbar.php";

// Pastikan user login sebagai konselor
if(!isset($_SESSION['id_user']) || $_SESSION['role'] != 'konselor'){
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
?>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Data Asesmen dan Konseling | Konselor</title>
</head>

<div id="content">
  <div class="container">
    <div class="row mt-3">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header">
            Data Asesmen dan Permintaan Konseling
          </div>
          <div class="card-body">
            <!-- Fungsi Cari dan FIlter -->
            <div class="d-flex justify-content-end mb-3">
                <form action="" method="GET" class="d-flex gap-2">
                    <input type="text" class="form-control" name="keyword"
                          placeholder="Cari Nama / NIM"
                          value="<?= $_GET['keyword'] ?? '' ?>">

                    <select name="filter" class="form-select">
                        <option value="">Semua Data</option>
                        <option value="az" <?= ($_GET['filter'] ?? '')=='az' ? 'selected' : '' ?>>Nama A–Z</option>
                        <option value="baru" <?= ($_GET['filter'] ?? '')=='baru' ? 'selected' : '' ?>>Data Terbaru</option>
                        <option value="disetujui" <?= ($_GET['filter'] ?? '')=='disetujui' ? 'selected' : '' ?>>Status Disetujui</option>
                        <option value="selesai" <?= ($_GET['filter'] ?? '')=='selesai' ? 'selected' : '' ?>>Status Selesai</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </form>
            </div>

            <table class="table table-bordered table-striped">
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIM</th>
                <th>Permasalahan</th>
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
// Jika tidak ada filter, cek apakah ada input 'status' (dari dashboard)
elseif (isset($_GET['status']) && $_GET['status'] != '') {
    // Ubah jadi huruf kecil semua biar cocok sama switch case (Selesai -> selesai)
    $filter = strtolower($_GET['status']); 
} 
// Default kosong
else {
    $filter = '';
}

// Ambil HANYA data konseling yang ditugaskan ke konselor login
$sql = "
SELECT a.id_asesmen, m.nama, m.nim, a.permasalahan,
       k.id_konseling, k.jadwal_konseling, k.lokasi, k.status, k.sesi
FROM konseling k
JOIN asesmen a ON k.id_asesmen = a.id_asesmen
JOIN mahasiswa m ON a.nim = m.nim
WHERE k.id_user = '$id_user'
AND 1=1
";

// Filter keyword
if(!empty($keyword)){
    $sql .= " AND (m.nama LIKE '%$keyword%' OR m.nim LIKE '%$keyword%')";
}

switch ($filter) {
    case 'az':
        $sql .= " ORDER BY m.nama ASC";
        break;

    case 'baru':
        $sql .= " ORDER BY a.tanggal_asesmen DESC";
        break;

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
        $sql .= " ORDER BY a.tanggal_asesmen ASC";
}

$result = mysqli_query($koneksi, $sql);
if(!$result){
    die("Query gagal: " . mysqli_error($koneksi));
}

if(mysqli_num_rows($result) > 0){
    $no = 1;
   while($row = mysqli_fetch_assoc($result)){
        
      // Ambil status dulu (biar tidak undefined)
        $status = $row['status'] ?? 'Menunggu';

        // Tombol aktif hanya saat Disetujui
        $isAktif    = ($status === 'Disetujui');
        $isSelesai  = ($status === 'Selesai');
        $isLanjutan = ($status === 'Lanjutan');

        // Hanya bisa diklik jika status 'Disetujui'
        $linkInput  = $isAktif ? "konselor_update.php?id_asesmen={$row['id_asesmen']}" : '#';

        // Logika Teks dan Warna Tombol
        if ($isSelesai) {
            // Jika Selesai: Teks 'Selesai', Warna Hijau (btn-success), Disabled
            $textInput  = 'Selesai';
            $classInput = 'btn btn-success btn-sm disabled'; 

        } elseif ($isLanjutan) {
            // Jika Lanjutan: Teks 'Sesi Berakhir', Warna Abu-abu (btn-secondary), Disabled
            $textInput  = 'Sesi Berakhir';
            $classInput = 'btn btn-secondary btn-sm disabled';

        } elseif ($isAktif) {
            // Jika Disetujui: Teks 'Update', Warna Biru (btn-primary), Aktif
            $textInput  = 'Update';
            $classInput = 'btn btn-primary btn-sm';

        } else {
            // Status lain (misal Menunggu): Default Abu-abu, Disabled
            $textInput  = 'Update';
            $classInput = 'btn btn-secondary btn-sm disabled';
}

        // FORMAT TANGGAL
        $jadwalRaw = $row['jadwal_konseling'];
        if (!empty($jadwalRaw) && $jadwalRaw != '0000-00-00 00:00:00') {
            $jamDisplay = date('d-m-Y H:i', strtotime($jadwalRaw)) . ' WIB';
        } else {
            $jamDisplay = '<span class="badge bg-secondary">Belum Dijadwalkan</span>';
        }
        
        echo "<tr>
                <td>{$no}</td>
                <td>".htmlspecialchars($row['nama'])."</td>
                <td>{$row['nim']}</td>
                <td>".htmlspecialchars($row['permasalahan'])."</td>
                <td>{$jamDisplay}</td>
                <td>".($row['lokasi'] ?? '-')."</td>
                <td>{$status}</td>

                <td>".($row['sesi'] ?? '1')."</td>
                
                <td>
                    <a href='{$linkInput}' class='{$classInput}'>{$textInput}</a>
                </td>

                <td>
                    <a href='konselor_lihatdetail.php?id_asesmen={$row['id_asesmen']}' class='btn btn-primary btn-sm'>Lihat Detail</a>
                </td>
              </tr>";
              
        $no++;
    }
} else {
    echo "<tr><td colspan='9' class='text-center'>Belum ada data konseling untuk Anda</td></tr>";
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