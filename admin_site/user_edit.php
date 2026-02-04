<?php
include "koneksi.php";
include "navbar.php";

$id_user = $_GET['id'];
$query = mysqli_query($koneksi, "SELECT * FROM user WHERE id_user='$id_user'");
$data = mysqli_fetch_array($query);
?>

<div id="content">
<div class="container">
    <div class="row mt-3">
        <div class="col-lg-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    Edit Status Pengguna
                </div>
                <div class="card-body">
                    <form action="user_update.php" method="POST">
                        <input type="hidden" name="id_user" value="<?= $data['id_user']; ?>">

                        <div class="mb-3">
                            <label class="form-label">Nama Pengguna</label>
                            <input type="text" class="form-control" value="<?= $data['nama_user']; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status Akun</label>
                            <select name="status" class="form-select">
                                <option value="1" <?= $data['status'] == 1 ? 'selected' : ''; ?>>Aktif (Bisa Login)</option>
                                <option value="0" <?= $data['status'] == 0 ? 'selected' : ''; ?>>Nonaktif (Tidak Bisa Login)</option>
                            </select>
                            <div class="form-text text-danger">
                                *Jika dinonaktifkan, user tidak bisa login tapi data asesmen tetap aman.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="user.php" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>