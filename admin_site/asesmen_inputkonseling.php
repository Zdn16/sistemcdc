<?php
include "koneksi.php";
include "navbar.php";

// Ambil id_asesmen dari URL
$id_asesmen = isset($_GET['id_asesmen']) ? (int)$_GET['id_asesmen'] : 0;

// Query data asesmen untuk mendapatkan NIM
$nim = null;

// kontrol form
$boleh_input = false;
$pesan_disable = '';
$sesi_otomatis = 1;
$default_konselor = null;

if ($id_asesmen > 0) {
    $query = mysqli_query($koneksi, "SELECT nim FROM asesmen WHERE id_asesmen = '$id_asesmen' LIMIT 1");
    if ($query && mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        $nim = $data['nim'];

        // Ambil sesi terakhir untuk asesmen 
        $q_last = mysqli_query($koneksi, "
            SELECT id_konseling, id_user, status, sesi
            FROM konseling
            WHERE id_asesmen = '$id_asesmen'
            ORDER BY sesi DESC, id_konseling DESC
            LIMIT 1
        ");

        if ($q_last && mysqli_num_rows($q_last) > 0) {
            $last = mysqli_fetch_assoc($q_last);
            $status_last = $last['status'] ?? '';
            $sesi_last   = (int)($last['sesi'] ?? 1);

            if ($status_last === 'Lanjutan') {
                // boleh buat jadwal sesi berikutnya
                $boleh_input = true;
                $sesi_otomatis = $sesi_last + 1;
                $default_konselor = (int)$last['id_user']; // konselor sama
            } elseif ($status_last === 'Disetujui') {
                // masih ada sesi aktif, admin tidak boleh buat jadwal baru
                $boleh_input = false;
                $pesan_disable = "Jadwal konseling untuk mahasiswa dengan NIM <strong>$nim</strong> sudah dibuat dan masih <strong>aktif</strong>. Menunggu konselor mengupdate status.";
            } elseif ($status_last === 'Selesai') {
                // sudah selesai, tidak boleh jadwalkan lagi
                $boleh_input = false;
                $pesan_disable = "Konseling untuk mahasiswa dengan NIM <strong>$nim</strong> sudah berstatus <strong>Selesai</strong>. Tidak dapat membuat jadwal baru.";
            } else {
                // status lain dianggap tidak boleh 
                $boleh_input = false;
                $pesan_disable = "Tidak dapat membuat jadwal baru karena status terakhir tidak valid.";
            }
        } else {
            // Belum pernah ada konseling -> input sesi 1
            $boleh_input = true;
            $sesi_otomatis = 1;
        }
    }
}
?>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Input Jadwal Konseling</title>
</head>

<div id="content">
<div class="container">
    <div class="row mt-3">
        <div class="col-lg-12 mt-2" style="min-height: 480px;">
            <div class="card">
                <div class="card-header">
                    Input Jadwal Konseling
                </div>
                <div class="card-body">

                    <?php if (!$nim): ?>
                        <div class="alert alert-danger">
                            Data asesmen tidak ditemukan! ID Asesmen: <?= $id_asesmen ?: 'kosong' ?>
                        </div>
                        <a href="asesmen.php" class="btn btn-secondary">Kembali</a>

                    <?php elseif (!$boleh_input): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <?= $pesan_disable ?: "Jadwal konseling sudah dibuat sebelumnya!" ?>
                        </div>
                        <a href="asesmen.php" class="btn btn-secondary">Kembali ke Daftar Asesmen</a>

                    <?php else: ?>

                    <div class="row">
                        <div class="col">
                            <form action="asesmen_simpankonseling.php" method="POST">
                                <input type="hidden" name="nim" value="<?= htmlspecialchars($nim) ?>">
                                <input type="hidden" name="id_asesmen" value="<?= htmlspecialchars((string)$id_asesmen) ?>">

                                <!-- Info -->
                                <div class="alert alert-info">
                                    NIM Mahasiswa: <strong><?= htmlspecialchars($nim) ?></strong>
                                </div>

                                <!-- Pilih Konselor -->
                                <div class="mb-3">
                                    <label class="form-label">Pilih Konselor</label>

                                    <?php
                                    // kalau default_konselor ada artinya sesi lanjutan → kunci pilihan
                                    $isLanjutan = ($default_konselor !== null);
                                    ?>

                                    <?php if ($isLanjutan): ?>
                                        <input type="hidden" name="id_user" value="<?= (int)$default_konselor ?>">

                                        <select class="form-select" disabled>
                                            <?php
                                            $qK = mysqli_query($koneksi, "SELECT id_user, nama_user FROM user WHERE role='konselor'");
                                            while($k = mysqli_fetch_assoc($qK)){
                                                if ((int)$k['id_user'] === (int)$default_konselor) {
                                                    echo '<option selected value="'.$k['id_user'].'">'.$k['nama_user'].'</option>';
                                                    break;
                                                }
                                            }
                                            ?>
                                        </select>

                                        <small class="text-muted">Konselor sesi lanjutan mengikuti konselor sesi sebelumnya.</small>

                                    <?php else: ?>

                                        <select name="id_user" class="form-select" required>
                                            <option value="">-- Pilih Konselor --</option>
                                            <?php
                                            $qK = mysqli_query($koneksi, "SELECT id_user, nama_user FROM user WHERE role='konselor'");
                                            while($k = mysqli_fetch_assoc($qK)){
                                                echo '<option value="'.$k['id_user'].'">'.$k['nama_user'].'</option>';
                                            }
                                            ?>
                                        </select>

                                    <?php endif; ?>
                                </div>


                                <!-- Jadwal Konseling -->
                                <div class="mb-3">
                                    <label class="form-label">Jadwal Konseling</label>
                                    <input type="datetime-local" name="jadwal_konseling" class="form-control" required>
                                </div>

                                <!-- Lokasi Konseling -->
                                <div class="mb-3">
                                    <label class="form-label">Lokasi Konseling</label>
                                    <select name="lokasi" class="form-select" required>
                                        <option value="">-- Pilih Lokasi Konseling --</option>
                                        <option value="online">Online</option>
                                        <option value="offline (Kantor CDC)">Offline (Kantor CDC)</option>
                                        <option value="offline (Kampus Jati Unand)">Offline (Kampus Jati Unand)</option>
                                    </select>
                                </div>

                                <!-- Sesi -->
                                <div class="mb-3">
                                    <label class="form-label">Sesi Ke-</label>
                                    <input type="number" name="sesi" class="form-control" value="<?= (int)$sesi_otomatis ?>" readonly>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    Simpan Jadwal
                                </button>
                                <a href="asesmen.php" class="btn btn-secondary">Kembali</a>
                            </form>
                        </div>
                    </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const toggleBtn = document.getElementById ("toggleSidebar");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  if (toggleBtn) {
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("active");
      content.classList.toggle("shift");
    });
  }
</script>

</body>
</html>
