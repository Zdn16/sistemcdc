<?php
session_start();
include "koneksi.php";
include "konselor_navbar.php";

// Pastikan user login sebagai konselor
if(!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') != 'konselor'){
    header("Location: login.php");
    exit;
}

$id_user = (int)$_SESSION['id_user'];

// Ambil id_asesmen dari URL
$id_asesmen = isset($_GET['id_asesmen']) ? (int)$_GET['id_asesmen'] : 0;

// Ambil NIM dari asesmen
$nim = null;
if ($id_asesmen > 0) {
    $qNim = mysqli_query($koneksi, "SELECT nim FROM asesmen WHERE id_asesmen = '$id_asesmen' LIMIT 1");
    if ($qNim && mysqli_num_rows($qNim) > 0) {
        $dNim = mysqli_fetch_assoc($qNim);
        $nim = $dNim['nim'];
    }
}

// Ambil sesi konseling AKTIF untuk konselor ini (status = Disetujui)
$data_lama = null;
$sudah_ada_jadwal = false;

if ($nim) {
    $qAktif = mysqli_query($koneksi, "
        SELECT *
        FROM konseling
        WHERE id_asesmen = '$id_asesmen'
          AND id_user = '$id_user'
          AND status = 'Disetujui'
        ORDER BY sesi DESC, id_konseling DESC
        LIMIT 1
    ");

    if ($qAktif && mysqli_num_rows($qAktif) > 0) {
        $data_lama = mysqli_fetch_assoc($qAktif);
        $sudah_ada_jadwal = true;
    }
}
?>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Input/Update Jadwal Konseling</title>
</head>

<body>
<div id="content">
<div class="container">
    <div class="row mt-3">
        <div class="col-lg-12 mt-2" style="min-height: 480px;">
            <div class="card">
                <div class="card-header">
                    <?= $sudah_ada_jadwal ? 'Update Status Konseling' : 'Input Jadwal Konseling Baru' ?>
                </div>
                <div class="card-body">

                    <?php if (!$nim): ?>
                        <div class="alert alert-danger">
                            Data asesmen tidak ditemukan! ID Asesmen: <?= htmlspecialchars((string)$id_asesmen) ?>
                        </div>
                        <a href="konselor_asesmen.php" class="btn btn-secondary">Kembali</a>

                    <?php elseif (!$sudah_ada_jadwal): ?>
                        <!-- Tidak ada sesi AKTIF (Disetujui) -->
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle"></i>
                            Tidak ada sesi konseling yang bisa diupdate saat ini untuk mahasiswa
                            <strong><?= htmlspecialchars($nim) ?></strong>.
                            <br>
                            Kemungkinan:
                            <ul class="mb-0">
                                <li>Masih menunggu admin menjadwalkan sesi baru, atau</li>
                                <li>Konseling sudah berstatus <strong>Selesai</strong>.</li>
                            </ul>
                        </div>
                        <a href="konselor_asesmen.php" class="btn btn-secondary">Kembali</a>

                    <?php else: ?>
                        <!-- Ada sesi AKTIF -->
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle"></i>
                            Jadwal untuk mahasiswa <strong><?= htmlspecialchars($nim) ?></strong> sudah ada. <br>
                            Anda dapat mengubah statusnya menjadi <strong>Lanjutan</strong> atau <strong>Selesai</strong> setelah konseling terlaksana.
                        </div>

                        <form action="konselor_update_status.php" method="POST">
                            <input type="hidden" name="id_konseling" value="<?= htmlspecialchars((string)$data_lama['id_konseling']) ?>">
                            <input type="hidden" name="id_asesmen" value="<?= htmlspecialchars((string)$id_asesmen) ?>">
                            <input type="hidden" name="nim" value="<?= htmlspecialchars($nim) ?>">

                            <div class="mb-3">
                                <label class="form-label">Tanggal Terjadwal</label>
                                <input type="text" class="form-control"
                                       value="<?= htmlspecialchars($data_lama['jadwal_konseling'] ?? '-') ?>"
                                       readonly disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Sesi Ke-</label>
                                <input type="text" class="form-control"
                                       value="<?= htmlspecialchars((string)($data_lama['sesi'] ?? '1')) ?>"
                                       readonly disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Update Status Aksi</label>
                                <select name="status_konseling" id="statusSelect" class="form-select" onchange="toggleCatatan()" required>
                                    <option value="" selected disabled>-- Pilih Status --</option>
                                    <option value="Lanjutan">Lanjutan (Butuh Sesi Berikutnya)</option>
                                    <option value="Selesai">Selesai (Konseling Berakhir)</option>
                                </select>
                            </div>

                            <div class="mb-3" id="boxCatatan" style="display: none;">
                                <div class="alert alert-primary py-2">
                                    <small><i class="bi bi-pencil-square"></i> Masukkan catatan hasil konseling:</small>
                                </div>
                                <textarea name="catatan" class="form-control" rows="5"
                                          placeholder="Tuliskan hasil konseling, solusi, atau rekomendasi untuk mahasiswa..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-success">Update Status</button>
                            <a href="konselor_asesmen.php" class="btn btn-secondary">Kembali</a>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Script Toggle Sidebar
  const toggleBtn = document.getElementById("toggleSidebar");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  if(toggleBtn){
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("active");
      content.classList.toggle("shift");
    });
  }

  // Script Toggle Catatan (Muncul jika status = Lanjutan / Selesai)
  function toggleCatatan() {
    var statusSelect = document.getElementById("statusSelect");
    var boxCatatan   = document.getElementById("boxCatatan");
    if (!statusSelect || !boxCatatan) return;

    var textArea = boxCatatan.querySelector("textarea");

    if (statusSelect.value === "Selesai" || statusSelect.value === "Lanjutan") {
      boxCatatan.style.display = "block";
      textArea.setAttribute("required", "required");
    } else {
      boxCatatan.style.display = "none";
      textArea.removeAttribute("required");
      textArea.value = "";
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    toggleCatatan();
  });
</script>

</body>
</html>
