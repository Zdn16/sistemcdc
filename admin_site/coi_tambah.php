<?php
    include "koneksi.php";
    include "navbar.php";
?>

<?php if (isset($_GET['error']) && $_GET['error'] == 'duplicate'): ?>
    <div class="alert alert-danger">Pertanyaan Sudah ada.</div>
<?php endif; ?>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Pertanyaan COI</title>
</head>

<div id="content">
<div class="container">
    <div class="row mt-3">
        <div class="col-lg-12 mt-2" style="min-height: 480px;">
            <div class="card">
                <div class="card-header">
                    Tambah Pertanyaan Career Anchor
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <form action="coi_simpan.php" method="POST">

                            <!-- Pertanyaan -->
                            <div class="mb-3">
                                <label class="form-label">Pertanyaan</label>
                                <textarea 
                                name="pertanyaan" 
                                class="form-control" 
                                rows="3" 
                                required
                                placeholder="Masukkan pertanyaan career anchor">
                                </textarea>
                            </div>

                            <!-- Kategori Career Anchor -->
                            <div class="mb-3">
                                <label class="form-label">Kategori Career Anchor</label>
                                <select name="kategori" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <option value="autonomy">Autonomy/Independence</option>
                                <option value="security">Security/Stability</option>
                                <option value="tf">Technical Function</option>
                                <option value="gm">General Management Competence</option>
                                <option value="ec">Entrepreneurial Creativity</option>
                                <option value="service">Service Dedication to a Cause</option>
                                <option value="challenge">Pure Challenge</option>
                                <option value="lifestyle">Lifestyle</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Simpan Pertanyaan
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


