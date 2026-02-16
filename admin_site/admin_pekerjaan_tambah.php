<?php
    include "koneksi.php";
    include "navbar.php";

    // ==========================================
    // AMBIL DATA JURUSAN GABUNG FAKULTAS
    // join tabel jurusan & fakultas agar nama fakultasnya juga terlihat di pilihan
    $query_jurusan = "SELECT j.id_jurusan, j.nama_jurusan, f.nama_fakultas 
                      FROM jurusan j 
                      JOIN fakultas f ON j.id_fakultas = f.id_fakultas 
                      ORDER BY f.nama_fakultas ASC, j.nama_jurusan ASC";
    
    $result_jurusan = mysqli_query($koneksi, $query_jurusan);

    if (!$result_jurusan) {
        die("Gagal mengambil data jurusan: " . mysqli_error($koneksi));
    }
?>

<?php if (isset($_GET['error']) && $_GET['error'] == 'duplicate'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Gagal!</strong> Pekerjaan tersebut sudah ada.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Profil Pekerjaan</title>
  <style>
    /* Sedikit CSS tambahan agar radio button di tengah sel tabel */
    .tbl-skor th, .tbl-skor td {
        text-align: center;
        vertical-align: middle;
    }
    .tbl-skor th.text-start, .tbl-skor td.text-start {
        text-align: left;
    }
    .form-check-inline {
        margin-right: 0; /* Reset margin bootstrap untuk penempatan di tengah */
    }
  </style>
</head>

<div id="content">
<div class="container">
    <div class="row mt-3">
        <div class="col-lg-12 mt-2 mb-5" style="min-height: 480px;">
            <div class="card">
                <div class="card-header">
                    Tambah Profil Pekerjaan
                </div>
                <div class="card-body">
                    <form action="admin_pekerjaan_simpan.php" method="POST">
                        
                        <h5 class="card-title mb-3 text-muted">Data Umum</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_pekerjaan" class="form-label">Nama Pekerjaan</label>
                                    <input type="text" class="form-control" id="nama_pekerjaan" name="nama_pekerjaan" placeholder="Masukkan Nama Pekerjaan" required>
                                </div>
                            </div>
                             <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_jurusan" class="form-label">Jurusan / Departemen terkait</label>
                                    <select name="id_jurusan" class="form-control form-select" required>
                                        <option value="">-- Pilih Departemen --</option>
                                        <?php 
                                        if(mysqli_num_rows($result_jurusan) > 0){
                                            // Reset pointer data jika diperlukan, tapi disini aman karena baru pertama dipanggil
                                            while($j = mysqli_fetch_array($result_jurusan)) {
                                                // Menampilkan Fakultas - Jurusan agar lebih jelas
                                                echo "<option value='".$j['id_jurusan']."'>".$j['nama_fakultas']." - ".$j['nama_jurusan']."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="ket_pekerjaan" class="form-label">Keterangan / Deskripsi Pekerjaan</label>
                            <textarea class="form-control" id="ket_pekerjaan" name="ket_pekerjaan" rows="3" placeholder="Masukkan Deskripsi Singkat Pekerjaan" required></textarea>
                        </div>

                        <hr class="my-4">

                        <h5 class="card-title mb-3 text-muted">Penilaian Standar Skor (Career Anchor)</h5>
                        
                        <div class="alert alert-light border" role="alert" style="font-size: 0.9rem;">
                            <strong>Keterangan Skala:</strong> <br>
                            (1) Sangat Tidak Sesuai | (2) Tidak Sesuai | (3) Cukup Sesuai | (4) Sesuai | (5) Sangat Sesuai
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped tbl-skor">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start" style="width: 40%;">Dimensi / Aspek</th>
                                        <th style="width: 12%;">1 (STS)</th>
                                        <th style="width: 12%;">2 (TS)</th>
                                        <th style="width: 12%;">3 (CS)</th>
                                        <th style="width: 12%;">4 (S)</th>
                                        <th style="width: 12%;">5 (SS)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Daftar field database dan labelnya untuk looping
                                    $dimensi = [
                                        'pk_autonomy'   => 'Autonomy (Kemandirian)',
                                        'pk_security'   => 'Security (Keamanan/Stabilitas)',
                                        'pk_tf'         => 'Technical/Functional Competence',
                                        'pk_gm'         => 'General Managerial Competence',
                                        'pk_ec'         => 'Entrepreneurial Creativity',
                                        'pk_service'    => 'Service / Dedication to a Cause',
                                        'pk_challenge'  => 'Pure Challenge (Tantangan)',
                                        'pk_lifestyle'  => 'Lifestyle (Gaya Hidup)'
                                    ];

                                    foreach ($dimensi as $field_name => $label) : 
                                    ?>
                                    <tr>
                                        <td class="text-start fw-medium"><?= $label ?></td>
                                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                                        <td>
                                            <div class="form-check form-check-inline d-flex justify-content-center">
                                                <input class="form-check-input" type="radio" name="<?= $field_name ?>" value="<?= $i ?>" required style="cursor: pointer;">
                                            </div>
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 d-flex gap-2 justify-content-end">
                             <a href="admin_pekerjaan.php" class="btn btn-outline-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                Simpan Pekerjaan
                            </button>
                        </div>

                    </form>
                </div> </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Script toggle sidebar bawaan dari template Anda
  const toggleBtn = document.getElementById("toggleSidebar");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  if(toggleBtn){
      toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("active");
        content.classList.toggle("shift");
      });
  }
</script>

</body>
</html>