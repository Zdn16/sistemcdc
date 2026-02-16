<?php
session_start();
include "koneksi.php";

// 1. Cek Login
if (!isset($_SESSION['nim'])) {
    header("Location: login_mahasiswa.php");
    exit;
}

$nim = $_SESSION['nim'];
$msg = "";
$msg_type = "";

// 2. PROSES SIMPAN DATA
if (isset($_POST['simpan'])) {
    $nama           = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email          = mysqli_real_escape_string($koneksi, $_POST['email']);
    $no_hp          = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $jenis_kelamin  = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $angkatan       = mysqli_real_escape_string($koneksi, $_POST['angkatan']);
    $status_mahasiswa = mysqli_real_escape_string($koneksi, $_POST['status_mahasiswa']);
    $id_jurusan     = mysqli_real_escape_string($koneksi, $_POST['id_jurusan']);

    // --- PROSES UPLOAD FOTO ---
    $foto_db = ""; 
    $upload_ok = true;

    // Cek apakah ada file yang diupload
    if (!empty($_FILES['foto']['name'])) {
        $nama_file     = $_FILES['foto']['name'];
        $tmp_file      = $_FILES['foto']['tmp_name'];
        $ukuran_file   = $_FILES['foto']['size'];
        $tipe_file     = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
        
        // Folder tujuan (Pastikan folder 'uploads' ada di direktori Anda)
        $target_dir    = "uploads/";
        
        // Buat nama file unik agar tidak bentrok (NIM_time.jpg)
        $nama_file_baru = $nim . "_" . time() . "." . $tipe_file;
        $target_file    = $target_dir . $nama_file_baru;

        // Validasi Ekstensi
        $ekstensi_boleh = ['jpg', 'jpeg', 'png'];
        if (!in_array($tipe_file, $ekstensi_boleh)) {
            $msg = "Format foto harus JPG, JPEG, atau PNG.";
            $msg_type = "danger";
            $upload_ok = false;
        }
        // Validasi Ukuran (Max 2MB)
        elseif ($ukuran_file > 2000000) {
            $msg = "Ukuran foto terlalu besar (Max 2MB).";
            $msg_type = "danger";
            $upload_ok = false;
        }

        if ($upload_ok) {
            if (move_uploaded_file($tmp_file, $target_file)) {
                $foto_db = ", foto = '$nama_file_baru'"; 
            } else {
                $msg = "Gagal mengupload foto.";
                $msg_type = "danger";
                $upload_ok = false;
            }
        }
    }
    // ---------------------------

    if ($upload_ok) {
        // Update Data Utama (termasuk foto jika ada)
        $update_sql = "UPDATE mahasiswa SET 
                       nama = '$nama', 
                       email = '$email', 
                       no_hp = '$no_hp', 
                       jenis_kelamin = '$jenis_kelamin',
                       angkatan = '$angkatan',
                       status_mahasiswa = '$status_mahasiswa',
                       id_jurusan = '$id_jurusan' 
                       $foto_db 
                       WHERE nim = '$nim'";

        if (mysqli_query($koneksi, $update_sql)) {
            $msg = "Profil berhasil diperbarui.";
            $msg_type = "success";
            
            // Update Session Nama
            $_SESSION['nama'] = $nama;
        } else {
            $msg = "Gagal memperbarui profil: " . mysqli_error($koneksi);
            $msg_type = "danger";
        }
    }
}

// 3. AMBIL DATA MAHASISWA SAAT INI
$query_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE nim = '$nim'");
$mhs = mysqli_fetch_assoc($query_mhs);

// 4. AMBIL DATA JURUSAN
$query_jurusan = mysqli_query($koneksi, "SELECT * FROM jurusan ORDER BY nama_jurusan ASC");
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profil - CDC Mahasiswa</title>
    <link rel="icon" type="image/png" href="../foto/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f4f6f9; }
        .card-form { border: none; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .profile-pic-container { position: relative; width: 100px; height: 100px; margin: 0 auto; }
        .profile-pic { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn-upload { position: absolute; bottom: 0; right: 0; border-radius: 50%; padding: 5px 8px; font-size: 12px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="home_mahasiswa.php">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
        </a>
    </div>
</nav>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <?php if ($msg != ""): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card card-form">
                <div class="card-body p-4">
                    <form action="" method="POST" enctype="multipart/form-data">
                        
                        <div class="text-center mb-4">
                            <div class="profile-pic-container mb-3">
                                <?php 
                                    // Cek apakah user punya foto di database
                                    $foto_profil = "https://ui-avatars.com/api/?name=".urlencode($mhs['nama'])."&background=random&size=128";
                                    if (!empty($mhs['foto']) && file_exists("uploads/" . $mhs['foto'])) {
                                        $foto_profil = "uploads/" . $mhs['foto'];
                                    }
                                ?>
                                <img src="<?php echo $foto_profil; ?>" class="profile-pic" id="previewFoto">
                                
                                <label for="inputFoto" class="btn btn-sm btn-light border btn-upload shadow-sm cursor-pointer" title="Ganti Foto">
                                    <i class="fas fa-camera text-primary"></i>
                                </label>
                            </div>
                            
                            <h5 class="fw-bold mb-1">Edit Foto Profil</h5>
                            <small class="text-muted d-block mb-2">Klik ikon kamera untuk mengganti</small>
                            
                            <input type="file" name="foto" id="inputFoto" class="d-none" accept="image/*" onchange="previewImage(event)">
                            <div class="small text-danger" id="namaFileSelected"></div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label fw-bold">NIM (Tidak dapat diubah)</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($mhs['nim']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($mhs['nama']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($mhs['email']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">No. Handphone (WhatsApp)</label>
                                <input type="number" name="no_hp" class="form-control" value="<?php echo htmlspecialchars($mhs['no_hp'] ?? ''); ?>" placeholder="Contoh: 08123456789">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="Laki-laki" <?php if(($mhs['jenis_kelamin'] ?? '') == 'Laki-laki') echo 'selected'; ?>>Laki-laki</option>
                                    <option value="Perempuan" <?php if(($mhs['jenis_kelamin'] ?? '') == 'Perempuan') echo 'selected'; ?>>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tahun Angkatan</label>
                                <input type="number" name="angkatan" class="form-control" value="<?php echo htmlspecialchars($mhs['angkatan'] ?? ''); ?>" placeholder="Contoh: 2021">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Status Mahasiswa</label>
                                <select name="status_mahasiswa" class="form-select" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="Aktif" <?php if(($mhs['status_mahasiswa'] ?? '') == 'Aktif') echo 'selected'; ?>>Aktif</option>
                                    <option value="Alumni" <?php if(($mhs['status_mahasiswa'] ?? '') == 'Alumni') echo 'selected'; ?>>Alumni</option>
                                </select>
                            </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Departemen</label>
                            <select name="id_jurusan" class="form-select" required>
                                <option value="">-- Pilih Departemen --</option>
                                <?php while ($row_jur = mysqli_fetch_assoc($query_jurusan)): ?>
                                    <option value="<?php echo $row_jur['id_jurusan']; ?>" 
                                        <?php if($mhs['id_jurusan'] == $row_jur['id_jurusan']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($row_jur['nama_jurusan']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="home_mahasiswa.php" class="btn btn-secondary px-4">Batal</a>
                            <button type="submit" name="simpan" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i> Simpan Perubahan
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('previewFoto');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
        
        // Tampilkan nama file
        var fileName = event.target.files[0].name;
        document.getElementById('namaFileSelected').innerText = "File terpilih: " + fileName;
    }
</script>

</body>
</html>