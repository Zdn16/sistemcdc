<?php
session_start();
include "koneksi.php";

if (isset($_POST['register'])) {
    
    $nim            = mysqli_real_escape_string($koneksi, $_POST['nim']);
    $nama           = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $jenis_kelamin  = mysqli_real_escape_string($koneksi, $_POST['jk']); 
    $tanggal_lahir  = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $no_hp          = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $email          = mysqli_real_escape_string($koneksi, $_POST['email']);
    
    $id_jurusan     = isset($_POST['id_jurusan']) ? (int)$_POST['id_jurusan'] : 0;
    $angkatan       = isset($_POST['angkatan']) ? (int)$_POST['angkatan'] : 0;
    
    $password_plain = mysqli_real_escape_string($koneksi, $_POST['password']);

    // Validasi wajib isi kecuali foto dan status
    if (empty($nim) || empty($nama) || empty($jenis_kelamin) || empty($tanggal_lahir) || 
        empty($no_hp) || empty($email) || empty($id_jurusan) || empty($angkatan) || empty($password_plain)
        || empty($status_mahasiswa)) {
        
        echo "<script>
                alert('Harap lengkapi semua data! (Kecuali foto boleh kosong)'); 
                window.history.back();
              </script>";
        exit;
    }

    // Cek duplikat NIM
    $cek = mysqli_query($koneksi, "SELECT nim FROM mahasiswa WHERE nim = '$nim'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('NIM $nim sudah terdaftar!'); window.history.back();</script>";
        exit;
    }

    // Pasword Hashing
    $password_hash  = password_hash($password_plain, PASSWORD_DEFAULT);

    // Bagian Foto
    $path_foto = ""; // Default kosong
    if (!empty($_FILES['foto']['name'])) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $new_name = $nim . "_" . time() . "." . $ext;
        $target_file = $target_dir . $new_name;

        // Validasi ekstensi gambar sederhana
        $allowed = ['jpg', 'jpeg', 'png'];
        if(in_array($ext, $allowed)){
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                $path_foto = $target_file;
            } else {
                echo "<script>alert('Gagal upload foto, data tetap disimpan tanpa foto.');</script>";
            }
        } else {
             echo "<script>alert('Format foto harus JPG/PNG. Data tersimpan tanpa foto.');</script>";
        }
    }

    // Insert DB
    $query = "INSERT INTO mahasiswa (nim, nama, jenis_kelamin, tanggal_lahir, no_hp, email, password, id_jurusan, angkatan, foto, status_mahasiswa) 
              VALUES ('$nim', '$nama', '$jenis_kelamin', '$tanggal_lahir', '$no_hp', '$email', '$password_hash', '$id_jurusan', '$angkatan', '$path_foto', '$status_mahasiswa')";

    if (mysqli_query($koneksi, $query)) {
        echo "<script>
            alert('Registrasi Berhasil! Silakan Login.'); 
            window.location='login_mahasiswa.php'; 
        </script>";
    } else {
        echo "<script>alert('Error Database: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
    }

} else {
    header("Location: register_mahasiswa.php");
    exit;
}
?>