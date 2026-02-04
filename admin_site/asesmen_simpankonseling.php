<?php
session_start();
include "koneksi.php";

// ===========================
// 1. AMBIL DATA DARI FORM
// ===========================
$id_user      = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 0;
$id_asesmen       = isset($_POST['id_asesmen']) ? (int)$_POST['id_asesmen'] : 0;
$jadwal_input     = $_POST['jadwal_konseling'] ?? null;
$lokasi = $_POST['lokasi'] ?? null;

// ===========================
// 2. VALIDASI DATA KOSONG
// ===========================
if ($id_user <= 0 || $id_asesmen <= 0 || !$jadwal_input || !$lokasi) {
    echo "<script>alert('Data tidak lengkap!'); window.history.back();</script>";
    exit;
}

// ===========================
// 3. NORMALISASI FORMAT datetime-local -> MySQL DATETIME
//    "YYYY-MM-DDTHH:MM" -> "YYYY-MM-DD HH:MM:00"
// ===========================
$jadwal_konseling = str_replace('T', ' ', $jadwal_input);
if (strlen($jadwal_konseling) === 16) {
    $jadwal_konseling .= ":00";
}

// ===========================
// 4. AMBIL DATA TERAKHIR UNTUK LOGIKA SESI LANJUTAN
//    - jika belum ada -> sesi 1
//    - jika ada -> hanya boleh tambah sesi jika status terakhir = Lanjutan
//    - konselor harus SAMA dengan sesi sebelumnya (kalau lanjutan)
// ===========================
$sesi_final   = 1;
$status_final = 'Disetujui';
$catatan_final = '';

$q_last = mysqli_query($koneksi, "
    SELECT id_konseling, id_user, status, sesi
    FROM konseling
    WHERE id_asesmen = '$id_asesmen'
    ORDER BY sesi DESC, id_konseling DESC
    LIMIT 1
");

if ($q_last && mysqli_num_rows($q_last) > 0) {
    $last = mysqli_fetch_assoc($q_last);
    $status_last = $last['status'] ?? '';
    $sesi_last   = (int)($last['sesi'] ?? 1);
    $konselor_last = (int)($last['id_user'] ?? 0);

    if ($status_last !== 'Lanjutan') {
        echo "<script>
                alert('Tidak dapat menjadwalkan sesi baru. Status terakhir bukan Lanjutan.');
                window.history.back();
              </script>";
        exit;
    }

    // lanjutan -> sesi naik otomatis
    $sesi_final = $sesi_last + 1;

    // paksa konselor sama
    if ($konselor_last > 0) {
        $id_user = $konselor_last;
    }
}

// ===========================
// 5. CEK JADWAL BENTROK (konselor sama & waktu sama)
// ===========================
$query_cek = "SELECT id_konseling FROM konseling 
              WHERE id_user = ? 
              AND jadwal_konseling = ?";

$stmt_cek = mysqli_prepare($koneksi, $query_cek);

if (!$stmt_cek) {
    die("Query Cek Gagal: " . mysqli_error($koneksi));
}

mysqli_stmt_bind_param($stmt_cek, "is", $id_user, $jadwal_konseling);
mysqli_stmt_execute($stmt_cek);
mysqli_stmt_store_result($stmt_cek);

if (mysqli_stmt_num_rows($stmt_cek) > 0) {
    mysqli_stmt_close($stmt_cek);
    echo "<script>
            alert('GAGAL: Jadwal bentrok! Konselor tersebut sudah memiliki jadwal pada Tanggal dan Jam yang sama.');
            window.history.back();
          </script>";
    exit;
}
mysqli_stmt_close($stmt_cek);

// ===========================
// 6. INSERT SESI BARU
// ===========================
$query = "INSERT INTO konseling 
            (id_user, id_asesmen, jadwal_konseling, lokasi, status, sesi, catatan)
          VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($koneksi, $query);
if (!$stmt) {
    die("Query Insert Gagal: " . mysqli_error($koneksi));
}

mysqli_stmt_bind_param(
    $stmt,
    "iisssis",
    $id_user,
    $id_asesmen,
    $jadwal_konseling,
    $lokasi,
    $status_final,
    $sesi_final,
    $catatan_final
);

$execute = mysqli_stmt_execute($stmt);

// ===========================
// 7. HASIL & REDIRECT
// ===========================
if ($execute) {
    mysqli_stmt_close($stmt);
    echo "<script>
            alert('Jadwal konseling berhasil disimpan! (Sesi ke-$sesi_final)');
            window.location='asesmen.php';
          </script>";
    exit;
} else {
    die("Gagal menyimpan jadwal: " . mysqli_error($koneksi));
}
?>
