<?php
session_start();
include "koneksi.php";

// ambil semua fakultas
$fakultasResult = mysqli_query($koneksi, "SELECT id_fakultas, nama_fakultas FROM fakultas ORDER BY nama_fakultas ASC");
if(!$fakultasResult){
  die("Gagal ambil fakultas: " . mysqli_error($koneksi));
}
?>


<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Data Diri | CDC Universitas Andalas</title>

  <link rel="stylesheet" href="style_asesmen.css">
  <link rel="icon" type="image/png" href="../foto/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="bg-blur">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="card shadow rounded-4">
        <div class="card-body p-5">

          <h3 class="fw-bold mb-2">Form Data Diri</h3>
          <p class="text-muted mb-4">
            Career Development Center Universitas Andalas
          </p>

          <form action="proses_datadiri.php" method="post">

            <!-- Nama -->
            <div class="mb-3">
              <label for="nama" class="form-label">Nama Lengkap</label>
              <input type="text" id="nama" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required>
            </div>

            <!-- Tanggal Lahir -->
            <div class="mb-3">
                <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required>
            </div>

            <!-- NIM -->
            <div class="mb-3">
              <label for ="nim" class="form-label">NIM</label>
              <input type="text" id="nim" name="nim" class="form-control" placeholder="Masukkan NIM" required>
            </div>

            <!-- Email -->
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email" class="form-control" placeholder="email@student.unand.ac.id" required>
            </div>

            <!-- No HP -->
            <div class="mb-3">
            <label for="no_hp" class="form-label">No HP</label>
            <input type="text" class="form-control" id="no_hp" name="no_hp" placeholder="08xxxxxxxxxx" required>
            </div>

           <!-- Fakultas & Jurusan -->
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="id_fakultas" class="form-label">Fakultas</label>
                <select id="id_fakultas" name="id_fakultas" class="form-select" required>
                  <option value="">-- Pilih Fakultas --</option>
                  <?php while($f = mysqli_fetch_assoc($fakultasResult)): ?>
                    <option value="<?= (int)$f['id_fakultas']; ?>">
                      <?= htmlspecialchars($f['nama_fakultas']); ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <label for="id_jurusan" class="form-label">Departemen</label>
                <select id="id_jurusan" name="id_jurusan" class="form-select" required disabled>
                  <option value="">-- Pilih Fakultas dulu --</option>
                </select>
              </div>
            </div>

            <!-- Angkatan -->
            <div class="mb-3">
              <label for="angkatan" class="form-label">Angkatan</label>
              <select id="angkatan" name="angkatan" class="form-select" required>
                <option value="">-- Pilih Angkatan --</option>
                <option>2013</option>
                <option>2014</option>
                <option>2015</option>
                <option>2016</option>
                <option>2017</option>
                <option>2018</option>
                <option>2019</option>
                <option>2020</option>
                <option>2021</option>
                <option>2022</option>
                <option>2023</option>
                <option>2024</option>
                <option>2025</option>
              </select>
            </div>

            <!-- Jenis Kelamin -->
            <div class="mb-3">
              <label for="jk" class="form-label">Jenis Kelamin</label>
              <select id="jk" name="jk" class="form-select" required>
                <option value="">-- Pilih --</option>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
              </select>
            </div>

            <!-- Tombol -->
            <div class="d-flex justify-content-between mt-4">
              <a href="opening.php" class="btn btn-secondary"> Kembali </a>
              <button type="submit" class="btn btn-primary"> Lanjut </button>

            </div>

          </form>

        </div>
      </div>
</div>
    </div>
  </div>
</div>

<script>
const fakultasSelect = document.getElementById('id_fakultas');
const jurusanSelect  = document.getElementById('id_jurusan');

fakultasSelect.addEventListener('change', async () => {
  const idFakultas = fakultasSelect.value;

  jurusanSelect.innerHTML = '<option value="">-- Pilih Departemen --</option>';
  jurusanSelect.disabled = true;

  if (!idFakultas) {
    jurusanSelect.innerHTML = '<option value="">-- Pilih Fakultas dulu --</option>';
    return;
  }

  try {
    const res = await fetch('ajax_get_jurusan.php?id_fakultas=' + encodeURIComponent(idFakultas));
    const data = await res.json();

    if (!Array.isArray(data) || data.length === 0) {
      jurusanSelect.innerHTML = '<option value="">-- Jurusan tidak ditemukan --</option>';
      jurusanSelect.disabled = true;
      return;
    }

    data.forEach(item => {
      const opt = document.createElement('option');
      opt.value = item.id_jurusan;
      opt.textContent = item.nama_jurusan;
      jurusanSelect.appendChild(opt);
    });

    jurusanSelect.disabled = false;

  } catch (err) {
    jurusanSelect.innerHTML = '<option value="">-- Gagal memuat jurusan --</option>';
    jurusanSelect.disabled = true;
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
