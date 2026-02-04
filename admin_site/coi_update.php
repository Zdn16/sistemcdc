<?php
include "koneksi.php";

// Cek apakah form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Ambil data dari form
    $id_item    = $_POST['id_item'];
    $pertanyaan = $_POST['pertanyaan'];
    $kategori   = $_POST['kategori'];

    // 2. Sanitasi data untuk mencegah SQL Injection
    $id_item    = mysqli_real_escape_string($koneksi, $id_item);
    $pertanyaan = mysqli_real_escape_string($koneksi, $pertanyaan);
    $kategori   = mysqli_real_escape_string($koneksi, $kategori);

    // 3. Query Update
    $query = "UPDATE item_pertanyaan SET 
              pertanyaan = '$pertanyaan', 
              kategori = '$kategori' 
              WHERE id_item = '$id_item'";

    // 4. Eksekusi Query
    if (mysqli_query($koneksi, $query)) {
        // Jika berhasil, kembali ke halaman utama coi.php
        echo "<script>
                alert('Data berhasil diperbarui!');
                window.location.href = 'coi.php';
              </script>";
    } else {
        // Jika gagal
        echo "Error updating record: " . mysqli_error($koneksi);
    }

} else {
    // Jika user mencoba akses file ini langsung tanpa lewat form
    header("Location: coi.php");
    exit();
}
?>