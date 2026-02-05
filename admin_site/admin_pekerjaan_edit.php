<?php
session_start(); // Tambahkan session start jika perlu cek login
include "koneksi.php";
include "navbar.php";

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID pekerjaan tidak ditemukan");
}

// Gunakan id_pekerjaan untuk ambil data
$query = "SELECT * FROM profil_pekerjaan WHERE id_pekerjaan = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data pekerjaan tidak ditemukan");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profil Pekerjaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            Edit Profil Pekerjaan
        </div>
        <div class="card-body">

            <form action="admin_pekerjaan_update.php" method="POST">
                <input type="hidden" name="id_pekerjaan" value="<?= $data['id_pekerjaan']; ?>">

                <div class="mb-3">
                    <label class="form-label">Nama Pekerjaan</label>
                    <input type="text" name="nama_pekerjaan" class="form-control"
                           value="<?= $data['nama_pekerjaan']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan Pekerjaan</label>
                    <input type="text" name="ket_pekerjaan" class="form-control"
                           value="<?= $data['ket_pekerjaan']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Departemen</label>
                    <select name="id_jurusan" class="form-control" required>
                        <option value="">-- Pilih Departemen --</option>
                        <?php
                        // Ambil data master jurusan
                        $sql_jur = mysqli_query($koneksi, "SELECT * FROM jurusan ORDER BY nama_jurusan ASC");
                        while($j = mysqli_fetch_array($sql_jur)) {
                            // Cek jika ID jurusan di database sama dengan ID di loop, tambahkan 'selected'
                            $selected = ($data['id_jurusan'] == $j['id_jurusan']) ? 'selected' : '';
                            
                            echo "<option value='".$j['id_jurusan']."' $selected>".$j['nama_jurusan']."</option>";
                        }
                        ?>
                    </select>
                </div>

                <?php
                $fields = [
                    'pk_autonomy' => 'Autonomy',
                    'pk_security' => 'Security',
                    'pk_tf' => 'Technical Function',
                    'pk_gm' => 'General Managerial',
                    'pk_ec' => 'Entrepreneurial Creativity',
                    'pk_service' => 'Service Dedication',
                    'pk_challenge' => 'Pure Challenge',
                    'pk_lifestyle' => 'Lifestyle'
                ];

                foreach ($fields as $key => $label): ?>
                    <div class="mb-3">
                        <label class="form-label">Skor <?= $label ?></label>
                        <input type="number" name="<?= $key ?>" class="form-control"
                               value="<?= $data[$key]; ?>" required>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary">Update</button>
                <a href="admin_pekerjaan.php" class="btn btn-secondary">Kembali</a>

            </form>

        </div>
    </div>
</div>

</body>
</html>