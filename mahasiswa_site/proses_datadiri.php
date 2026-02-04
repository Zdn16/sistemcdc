<?php
session_start();
include "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil input dasar
    $nim_input  = mysqli_real_escape_string($koneksi, $_POST['nim']);
    $nama_input = mysqli_real_escape_string($koneksi, $_POST['nama']);

    // ============================================================
    // VALIDASI NIM & NAMA
    // ============================================================
    $cek_query = mysqli_query($koneksi, "SELECT nama FROM mahasiswa WHERE nim = '$nim_input'");

    if (mysqli_num_rows($cek_query) > 0) {
        $data_db = mysqli_fetch_assoc($cek_query);
        $nama_asli_di_db = $data_db['nama'];

        if (strtolower($nama_asli_di_db) !== strtolower($nama_input)) {
            echo "<script>
                alert('MAAF, NIM TIDAK COCOK!\\n\\nNIM $nim_input sudah terdaftar atas nama: \"$nama_asli_di_db\"');
                window.history.back();
            </script>";
            exit;
        }
    }

    // ============================================================
    // SIMPAN KE SESSION (PAKAI ID, BUKAN TEXT)
    // ============================================================
    $_SESSION['nim']            = $_POST['nim'];
    $_SESSION['nama']           = $_POST['nama'];
    $_SESSION['jk']             = $_POST['jk'];
    $_SESSION['tanggal_lahir']  = $_POST['tanggal_lahir'];
    $_SESSION['no_hp']          = $_POST['no_hp'];
    $_SESSION['email']          = $_POST['email'];
    $_SESSION['id_fakultas']    = (int)$_POST['id_fakultas'];
    $_SESSION['id_jurusan']     = (int)$_POST['id_jurusan'];
    $_SESSION['angkatan']       = $_POST['angkatan'];

    header("Location: asesmen2.php");
    exit;
}
?>
