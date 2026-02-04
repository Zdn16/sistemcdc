<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['nim'])) {
    die("Session NIM tidak ditemukan, silakan login ulang");
}

/* QUERY PERTANYAAN */
$query = mysqli_query($koneksi, "SELECT * FROM item_pertanyaan ORDER BY id_item");

if (!$query) {
    die("Query error: " . mysqli_error($koneksi));
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Asesmen Karier | CDC Unand</title>
  <link rel="stylesheet" href="style_asesmen.css">
  <link rel="icon" type="image/png" href="../foto/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="bg-blur py-5">
<div class="container">
<div class="row justify-content-center">
<div class="col-lg-9">

<div class="card shadow rounded-4">
<div class="card-body p-5">

<h4 class="fw-bold mb-3">Asesmen Karier</h4>

<div class="mb-4">
    <h6 class="fw-bold text-dark">Cara Pengisian</h6>
    <p class="text-muted">
        Untuk masing-masing dari 40 pernyataan, berilah tanda pada pilihan angka yang sesuai dengan keadaan Anda. 
        Anda akan memberikan nilai dari 1 sampai 6. Semakin tinggi angkanya, maka semakin sesuai pernyataan tersebut dengan keadaan Anda.
    </p>
</div>

<div class="mb-4">
    <h6 class="fw-bold text-dark">Petunjuk Nilai:</h6>
    <ul class="list-unstyled text-muted ps-0">
        <li class="mb-1"><span class="fw-bold text-primary">1</span> : Sangat tidak sesuai dengan diri Anda</li>
        <li class="mb-1"><span class="fw-bold text-primary">2</span> : Tidak sesuai dengan diri Anda</li>
        <li class="mb-1"><span class="fw-bold text-primary">3</span> : Kurang sesuai dengan diri Anda</li>
        <li class="mb-1"><span class="fw-bold text-primary">4</span> : Cukup sesuai dengan diri Anda</li>
        <li class="mb-1"><span class="fw-bold text-primary">5</span> : Sesuai dengan diri Anda</li>
        <li class="mb-1"><span class="fw-bold text-primary">6</span> : Sangat sesuai dengan diri Anda</li>
    </ul>
</div>

<hr class="my-4">

<form action="proses_asesmen3.php" method="post" id="formAsesmen">

<?php $no = 1; while ($row = mysqli_fetch_assoc($query)) { ?>
  <div class="mb-4">
    <p class="fw-semibold mb-2">
      <?= $no++ ?>. <?= $row['pertanyaan'] ?>
    </p>

    <small class="text-muted d-block mb-1">
    1 = Sangat Tidak Sesuai &nbsp;&nbsp; 6 = Sangat Sesuai
    </small>

    <div class="d-flex gap-4">
      <?php for ($i = 1; $i <= 6; $i++) { ?>
        <div class="form-check form-check-inline">
          <input class="form-check-input"
                 type="radio"
                 name="jawaban[<?= $row['id_item'] ?>]"
                 id="q<?= $row['id_item'] ?>_<?= $i ?>"
                 value="<?= $i ?>"
                 required>
          <label class="form-check-label" for="q<?= $row['id_item'] ?>_<?= $i ?>">
            <?= $i ?>
          </label>
        </div>
      <?php } ?>
    </div>
  </div>
<?php } ?>


<div class="d-flex justify-content-between mt-4">
  <a href="asesmen2.php" class="btn btn-secondary">Kembali</a>
  <button type="button" class="btn btn-primary" id="btnSelesai">Selesai</button>
</div>

</form>

</div>
</div>

</div>
</div>
</div>
</div>
<div class="modal fade" id="modalKonfirmasi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Konfirmasi Penyimpanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Data kamu akan disimpan secara permanen dan digunakan sebagai dasar untuk menentukan <strong>Asesmen Karier</strong>.</p>
        <p>Data kamu akan dijaga privasinya oleh <strong>Career Development Center Universitas Andalas</strong> dan tidak akan disalahgunakan</p>
        <p class="text-muted small">Pastikan semua jawaban sudah jujur dan sesuai. Hasil analisis juga akan dikirimkan ke email kamu.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btnKirimReal">Ya, Kirim & Simpan</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Ambil elemen tombol dan form
    const btnSelesai = document.getElementById('btnSelesai');
    const btnKirimReal = document.getElementById('btnKirimReal');
    const formAsesmen = document.getElementById('formAsesmen');
    
    // Saat tombol 'Selesai' diklik
    btnSelesai.addEventListener('click', function() {
        // Cek apakah semua radio button sudah dipilih (Validasi HTML5)
        if (formAsesmen.checkValidity()) {
            // Jika valid, munculkan Pop-up Modal
            const myModal = new bootstrap.Modal(document.getElementById('modalKonfirmasi'));
            myModal.show();
        } else {
            // Jika belum diisi semua, munculkan peringatan browser
            formAsesmen.reportValidity();
        }
    });

    // Saat tombol 'Ya, Kirim & Simpan' di dalam Pop-up diklik
    btnKirimReal.addEventListener('click', function() {
        // Kirim form ke proses_asesmen3.php (Masuk DB & Kirim Email)
        formAsesmen.submit();
    });
</script>
</body>
</html>
