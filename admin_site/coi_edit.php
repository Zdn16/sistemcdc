<?php
include "koneksi.php";
include "navbar.php";

// 1. Cek apakah ada ID di URL
if (!isset($_GET['id'])) {
    header("Location: coi.php");
    exit;
}

// 2. Ambil ID dan sanitasi
$id_item = mysqli_real_escape_string($koneksi, $_GET['id']);

// 3. Query data lama
$query = "SELECT * FROM item_pertanyaan WHERE id_item = '$id_item'";
$result = mysqli_query($koneksi, $query);

// 4. Cek apakah data ditemukan
if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='coi.php';</script>";
    exit;
}

$data = mysqli_fetch_assoc($result);
?>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Pertanyaan COI</title>
</head>

<div id="content">
<div class="container">
    <div class="row mt-3">
        <div class="col-lg-12 mt-2" style="min-height: 480px;">
            <div class="card">
                <div class="card-header">
                    Edit Pertanyaan Career Anchor
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <form action="coi_update.php" method="POST">
                                
                                <input type="hidden" name="id_item" value="<?= $data['id_item']; ?>">

                                <div class="mb-3">
                                    <label class="form-label">Pertanyaan</label>
                                    <textarea 
                                    name="pertanyaan" 
                                    class="form-control" 
                                    rows="3" 
                                    required
                                    placeholder="Masukkan pertanyaan career anchor"><?= htmlspecialchars($data['pertanyaan']); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Kategori Career Anchor</label>
                                    <select name="kategori" class="form-select" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <option value="autonomy"  <?= $data['kategori'] == 'autonomy' ? 'selected' : ''; ?>>Autonomy/Independence</option>
                                        <option value="security"  <?= $data['kategori'] == 'security' ? 'selected' : ''; ?>>Security/Stability</option>
                                        <option value="tf"        <?= $data['kategori'] == 'tf' ? 'selected' : ''; ?>>Technical Function</option>
                                        <option value="gm"        <?= $data['kategori'] == 'gm' ? 'selected' : ''; ?>>General Management Competence</option>
                                        <option value="ec"        <?= $data['kategori'] == 'ec' ? 'selected' : ''; ?>>Entrepreneurial Creativity</option>
                                        <option value="service"   <?= $data['kategori'] == 'service' ? 'selected' : ''; ?>>Service Dedication to a Cause</option>
                                        <option value="challenge" <?= $data['kategori'] == 'challenge' ? 'selected' : ''; ?>>Pure Challenge</option>
                                        <option value="lifestyle" <?= $data['kategori'] == 'lifestyle' ? 'selected' : ''; ?>>Lifestyle</option>
                                    </select>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">
                                        Update Pertanyaan
                                    </button>
                                    <a href="coi.php" class="btn btn-secondary">Batal</a>
                                </div>

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