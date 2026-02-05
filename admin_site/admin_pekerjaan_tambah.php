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
</head>

<div id="content">
<div class="container">
    <div class="row mt-3">
        <div class="col-lg-12 mt-2" style="min-height: 480px;">
            <div class="card">
                <div class="card-header">
                    Tambah Profil Pekerjaan
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col">

                            <form action="admin_pekerjaan_simpan.php" method="POST">

                            <div class="mb-3">
                                <label for="nama_pekerjaan" class="form-label">Nama Pekerjaan</label>
                                <input type="text" class="form-control" id="nama_pekerjaan" name="nama_pekerjaan" placeholder="Masukkan Nama Pekerjaan" required>
                            </div>

                            <div class="mb-3">
                                <label for="ket_pekerjaan" class="form-label">Keterangan Pekerjaan</label>
                                <input type="text" class="form-control" id="ket_pekerjaan" name="ket_pekerjaan" placeholder="Masukkan Keterangan Pekerjaan" required>
                            </div>

                            <div class="mb-3">
                                <label for="id_jurusan" class="form-label">Jurusan Pekerjaan</label>
                            <select name="id_jurusan" class="form-control" required>
                                <option value="">-- Pilih Departemen --</option>
                                <?php 
                                $sql_jur = mysqli_query($koneksi, "SELECT * FROM jurusan");
                                while($j = mysqli_fetch_array($sql_jur)) {
                                    echo "<option value='".$j['id_jurusan']."'>".$j['nama_jurusan']."</option>";
                                }
                                ?>
                            </select>
                            </div>

                            <div class="mb-3">
                                <label for="pk_autonomy" class=form-label>Skor Autonomy</label>
                                <input type="number" class="form-control" id="pk_autonomy" name="pk_autonomy" placeholder="Masukan Standar Skor Autonomy" required>
                            </div>

                            <div class="mb-3">
                                <label for="pk_security" class=form-label>Skor Security</label>
                                <input type="number" class="form-control" id="pk_security" name="pk_security" placeholder="Masukan Standar Skor Security" required>
                            </div>

                            <div class="mb-3">
                                <label for="pk_tf" class=form-label>Skor Technical Function</label>
                                <input type="number" class="form-control" id="pk_tf" name="pk_tf" placeholder="Masukan Standar Skor Technical Function" required>
                            </div>

                            <div class="mb-3">
                                <label for="pk_gm" class=form-label>Skor General Managerial Competence</label>
                                <input type="number" class="form-control" id="pk_gm" name="pk_gm" placeholder="Masukan Standar Skor General Managerial Competence" required>
                            </div>

                            <div class="mb-3">
                                <label for="pk_ec" class=form-label>Skor Entrepreneurial Creativity</label>
                                <input type="number" class="form-control" id="pk_ec" name="pk_ec" placeholder="Masukan Standar Skor Entrepreneurial Creativity" required>
                            </div>

                            <div class="mb-3">
                                <label for="pk_service" class=form-label>Skor Service Dedication to a Cause</label>
                                <input type="number" class="form-control" id="pk_service" name="pk_service" placeholder="Masukan Standar Skor Service Dedication to a Cause" required>
                            </div>

                            <div class="mb-3">
                                <label for="pk_challenge" class=form-label>Skor Pure Challenge</label>
                                <input type="number" class="form-control" id="pk_challenge" name="pk_challenge" placeholder="Masukan Standar Skor Pure Challenge" required>
                            </div>

                            <div class="mb-3">
                                <label for="pk_lifestyle" class=form-label>Skor Lifestyle</label>
                                <input type="number" class="form-control" id="pk_lifestyle" name="pk_lifestyle" placeholder="Masukan Standar Skor Lifestyle" required>
                            </div>


                            <button type="submit" class="btn btn-primary">
                                Simpan Pekerjaan
                            </button>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const toggleBtn = document.getElementById("toggleSidebar");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    content.classList.toggle("shift");
  });
</script>

</body>
</html>