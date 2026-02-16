<?php
// Tampilkan semua error PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// 1. CEK LOGIN
// Karena sistem sudah login, kita cek Session NIM.
if (!isset($_SESSION['nim'])) {
    echo "<script>alert('Sesi habis. Silakan login kembali.'); window.location='login_mahasiswa.php';</script>";
    exit;
}

// Cek apakah ada jawaban dari form asesmen
if (empty($_POST['jawaban'])) {
    echo "<script>alert('Jawaban tidak terdeteksi. Silakan ulangi.'); window.history.back();</script>";
    exit;
}

include "koneksi.php";
include "profile_matching.php";
include "helper_email_template.php"; // <--- FILE BARU YANG KITA PISAH TADI

// LOAD PHPMAILER
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// FUNGSI FLASK (Biarkan di sini atau pindah ke helper juga boleh)
function cek_kategori_ke_flask($teks_masalah) {
    $url = 'http://127.0.0.1:5000/predict';
    $data = array('masalah' => $teks_masalah);
    $payload = json_encode($data);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 200 || !$result) {
        return "Belum Terklasifikasi (Flask Error)";
    }
    
    $response_data = json_decode($result, true);
    return $response_data['kategori'];
}


// 2. AMBIL DATA DARI DATABASE (BUKAN DARI SESSION INPUT LAGI)
$nim = mysqli_real_escape_string($koneksi, $_SESSION['nim']);

// Kita perlu ID Jurusan untuk Profile Matching, dan Email/Nama untuk kirim hasil
$query_mhs = mysqli_query($koneksi, "
    SELECT m.*, j.nama_jurusan, f.nama_fakultas 
    FROM mahasiswa m
    LEFT JOIN jurusan j ON m.id_jurusan = j.id_jurusan
    LEFT JOIN fakultas f ON j.id_fakultas = f.id_fakultas
    WHERE m.nim = '$nim'
");

$data_mhs = mysqli_fetch_assoc($query_mhs);

if (!$data_mhs) {
    die("Data mahasiswa tidak ditemukan. Silakan login ulang.");
}

// Set variabel untuk keperluan logic & email
$nama         = $data_mhs['nama'];
$email        = $data_mhs['email'];
$id_jurusan   = $data_mhs['id_jurusan'];
$nama_jurusan = $data_mhs['nama_jurusan'];
$nama_fakultas= $data_mhs['nama_fakultas'];

// Ambil Permasalahan (dari Session/Post asesmen sebelumnya)
$permasalahan = isset($_SESSION['permasalahan']) ? $_SESSION['permasalahan'] : '-';
$permasalahan = mysqli_real_escape_string($koneksi, $permasalahan);

date_default_timezone_set('Asia/Jakarta');
$tanggal = date("Y-m-d H:i:s");


// 3. PANGGIL AI
$kategori_hasil_prediksi = "Manual Check"; 
try {
    $kategori_hasil_prediksi = cek_kategori_ke_flask($permasalahan);
} catch (Exception $e) {
    $kategori_hasil_prediksi = "Error Koneksi AI";
}

// 4. TRANSAKSI DATABASE
mysqli_begin_transaction($koneksi);

try {
    // --- (HAPUS INSERT MAHASISWA KARENA SUDAH ADA) ---

    /* INSERT ASESMEN */
    $result = mysqli_query($koneksi, "
        INSERT INTO asesmen (nim, tanggal_asesmen, permasalahan, kategori_permasalahan)
        VALUES ('$nim', '$tanggal', '$permasalahan', '$kategori_hasil_prediksi')
    ");

    if (!$result) throw new Exception("Insert asesmen gagal: " . mysqli_error($koneksi));

    $id_asesmen = mysqli_insert_id($koneksi);

    /* INSERT JAWABAN & HITUNG TOTAL SKOR */
     $total_per_kategori = [
        'autonomy'    => 0,
        'security'    => 0,
        'tf'          => 0,
        'gm'          => 0,
        'ec'          => 0,
        'service'     => 0,
        'challenge'   => 0,
        'lifestyle'   => 0
    ];

    foreach ($_POST['jawaban'] as $id_item => $skor) {
        $id_item = mysqli_real_escape_string($koneksi, $id_item);
        $skor    = (int)$skor;

        mysqli_query($koneksi, "
            INSERT INTO jawaban_asesmen (id_asesmen, id_item, skor)
            VALUES ('$id_asesmen', '$id_item', '$skor')
        ");

        // Ambil kategori item
        $res = mysqli_query($koneksi, "SELECT kategori FROM item_pertanyaan WHERE id_item='$id_item'");
        $row = mysqli_fetch_assoc($res);
        $kategori = $row['kategori'];

        if (isset($total_per_kategori[$kategori])) {
            $total_per_kategori[$kategori] += $skor;
        }
    }

    /* UPDATE TOTAL SKOR DI TABEL ASESMEN */
    mysqli_query($koneksi, "
        UPDATE asesmen SET
            skor_autonomy    = {$total_per_kategori['autonomy']},
            skor_security    = {$total_per_kategori['security']},
            skor_tf          = {$total_per_kategori['tf']},
            skor_gm          = {$total_per_kategori['gm']},
            skor_ec          = {$total_per_kategori['ec']},
            skor_service     = {$total_per_kategori['service']},
            skor_challenge   = {$total_per_kategori['challenge']},
            skor_lifestyle   = {$total_per_kategori['lifestyle']}
        WHERE id_asesmen = $id_asesmen
    ");

    // Simpan Hasil COI Dominan
    $hasil_asesmen = array_keys($total_per_kategori, max($total_per_kategori))[0];
    
    mysqli_query($koneksi, "
        UPDATE asesmen SET hasil_asesmen = '$hasil_asesmen'
        WHERE id_asesmen = $id_asesmen
    ");

    /* HITUNG SPK PROFILE MATCHING */
    $skor_mahasiswa = [
        'pk_autonomy'  => $total_per_kategori['autonomy'],
        'pk_security'  => $total_per_kategori['security'],
        'pk_tf'        => $total_per_kategori['tf'],
        'pk_gm'        => $total_per_kategori['gm'],
        'pk_ec'        => $total_per_kategori['ec'],
        'pk_service'   => $total_per_kategori['service'],
        'pk_challenge' => $total_per_kategori['challenge'],
        'pk_lifestyle' => $total_per_kategori['lifestyle']
    ];

    // Gunakan $id_jurusan yang diambil dari database tadi
    $rekomendasi = hitungSPKProfileMatching($koneksi, $id_jurusan, $skor_mahasiswa);
    
    // Simpan Top 3 Rekomendasi
    $urutan = 1;
    foreach ($rekomendasi as $data_job) {
        if ($urutan > 3) break; 

        $nama_pekerjaan = mysqli_real_escape_string($koneksi, $data_job['pekerjaan']);
        $skor_akhir     = $data_job['nilai']; 

        $q_job = mysqli_query($koneksi, "SELECT id_pekerjaan FROM profil_pekerjaan WHERE nama_pekerjaan = '$nama_pekerjaan' LIMIT 1");
        $d_job = mysqli_fetch_assoc($q_job);

        if ($d_job) {
            $id_pekerjaan_db = $d_job['id_pekerjaan'];
            $query_insert_rek = "INSERT INTO hasil_rekomendasi (id_asesmen, id_pekerjaan, urutan_rekomendasi, hasil_skor) 
                                 VALUES ('$id_asesmen', '$id_pekerjaan_db', '$urutan', '$skor_akhir')";
            mysqli_query($koneksi, $query_insert_rek);
            $urutan++;
        }
    }

    mysqli_commit($koneksi);

    // ============================================================
    // TAMBAHAN: PANGGIL PYTHON (APP2.PY)
    // ============================================================
    $url_python = "http://127.0.0.1:5001/api/rekomendasi-final/" . $id_asesmen;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_python);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Tunggu maks 10 detik
    
    $response_python = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Opsional: Cek error jika perlu (bisa dihapus nanti)
    if ($http_code != 200) {
        // Jangan die(), biarkan lanjut email tapi catat error jika ada log
        // error_log("Gagal memanggil Python: " . $response_python);
    }

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    echo "<h3>Error Database:</h3>" . $e->getMessage();
    exit;
}

// 5. KIRIM EMAIL
try {
    $kamus_anchor = [
        'tf' => ['judul_lengkap' => 'Technical Functional', 'deskripsi' => 'Anda memiliki bakat teknis kuat.', 'alasan_karir' => 'Spesialisasi mendalam.'],
        'gm' => ['judul_lengkap' => 'General Managerial', 'deskripsi' => 'Ambisi memimpin.', 'alasan_karir' => 'Leadership.'],
        'autonomy' => ['judul_lengkap' => 'Autonomy', 'deskripsi' => 'Ingin kebebasan.', 'alasan_karir' => 'Fleksibilitas.'],
        'security' => ['judul_lengkap' => 'Security', 'deskripsi' => 'Ingin aman.', 'alasan_karir' => 'Stabilitas.'],
        'ec' => ['judul_lengkap' => 'Entrepreneurial', 'deskripsi' => 'Ingin berinovasi.', 'alasan_karir' => 'Bisnis.'],
        'service' => ['judul_lengkap' => 'Service', 'deskripsi' => 'Ingin membantu.', 'alasan_karir' => 'Sosial.'],
        'challenge' => ['judul_lengkap' => 'Challenge', 'deskripsi' => 'Suka tantangan.', 'alasan_karir' => 'Target tinggi.'],
        'lifestyle' => ['judul_lengkap' => 'Lifestyle', 'deskripsi' => 'Work-life balance.', 'alasan_karir' => 'Keseimbangan.']
    ];

    $info = isset($kamus_anchor[$hasil_asesmen]) ? $kamus_anchor[$hasil_asesmen] : ['judul_lengkap' => strtoupper($hasil_asesmen), 'deskripsi' => '-', 'alasan_karir' => '-'];
    
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true;
    $mail->Username   = 'cdcuniversitasandalas@gmail.com'; 
    $mail->Password   = 'wmjb qouw dcnp ixmw'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    $mail->setFrom('cdcuniversitasandalas@gmail.com', 'CDC Universitas Andalas');
    $mail->addAddress($email, $nama);

    $mail->isHTML(true);
    $mail->Subject = 'Hasil Asesmen Karier - CDC Unand';

    // AMBIL DATA REKOMENDASI UNTUK EMAIL
// AMBIL DATA REKOMENDASI UNTUK EMAIL
    $topJobs = [];
    $qTop = mysqli_query($koneksi, "
        SELECT p.nama_pekerjaan, p.ket_pekerjaan, 
               hr.hasil_skor, hr.hasil_skor_baru, hr.urutan_baru
        FROM hasil_rekomendasi hr
        JOIN profil_pekerjaan p ON p.id_pekerjaan = hr.id_pekerjaan
        WHERE hr.id_asesmen = '$id_asesmen'
        ORDER BY 
            CASE WHEN hr.urutan_baru > 0 THEN hr.urutan_baru ELSE hr.urutan_rekomendasi END ASC
        LIMIT 3
    ");
    while ($r = mysqli_fetch_assoc($qTop)) { $topJobs[] = $r; }

    // RENDER HTML MENGGUNAKAN HELPER
    $chartCOIHtml = renderCOIBarChartEmail($total_per_kategori);
    $topJobsHtml  = renderTopJobsEmail($topJobs);
    $anchorExplanationHtml = renderAnchorExplanation();

    $mail->Body = "
    <div style='font-family: Arial, Helvetica, sans-serif; color:#2c3e50; line-height:1.5; max-width:700px;'>
      
      <div style='background:#2c3e50;color:#fff;padding:18px 20px;border-radius:10px;'>
        <h2 style='margin:0;font-size:18px;'>Hasil Asesmen Karier CDC Unand</h2>
        <p style='margin:6px 0 0 0;font-size:13px;opacity:0.9;'>
          Nama: <strong>{$nama}</strong> &nbsp;|&nbsp; NIM: <strong>{$nim}</strong>
        </p>
        <p style='margin:4px 0 0 0;font-size:13px;opacity:0.9;'>
          Fakultas: <strong>{$nama_fakultas}</strong> &nbsp;|&nbsp; Jurusan: <strong>{$nama_jurusan}</strong>
        </p>
      </div>

      <p style='margin-top:16px;'>
        Terima kasih telah menyelesaikan asesmen CDC Unand. Berikut ringkasan profil karier Anda:
      </p>

      {$chartCOIHtml}

      <div style='background:#f9f9f9;padding:15px;border-left:5px solid #2c3e50;border-radius:8px;margin-bottom:18px;'>
        <h4 style='margin:0 0 6px 0;font-size:14px;'>Tipe Career Anchor Dominan</h4>
        <h2 style='color:#2980b9;margin:0;font-size:20px;'>{$info['judul_lengkap']}</h2>
        <p style='margin:8px 0 0 0;'><em>{$info['deskripsi']}</em></p>
        <p style='margin:0;'>{$info['alasan_karir']}</p>
      </div>

      <h4 style='margin:0 0 8px 0;font-size:14px;'>Top 3 Rekomendasi Karier</h4>
      {$topJobsHtml}
      {$anchorExplanationHtml}

      <div style='margin-top:16px;padding:12px 14px;background:#fff7e6;border:1px solid #ffe1a3;border-radius:10px;'>
        <p style='margin:0;font-size:13px;'>
          <strong>Info Konseling:</strong> Silakan cek jadwal konseling di Dashboard Mahasiswa.
        </p>
      </div>

      <p style='margin-top:16px;font-size:13px;color:#666;'>
        Salam,<br><strong>CDC Unand</strong>
      </p>

    </div>
    ";

    $mail->send();
    
    // REDIRECT KE HALAMAN HASIL DETAIL
    echo "<script>
        alert('Selamat! Asesmen selesai. Hasil telah dikirim ke email Anda.');
        window.location.href = 'home_mahasiswa.php'; 
    </script>";
    exit; 

} catch (Exception $e) {
    // Tampilkan error asli di layar agar ketahuan salahnya
    echo "<h1>Gagal Kirim Email</h1>";
    echo "Pesan Error Mailer: " . $mail->ErrorInfo; 
    echo "<br><br>Pesan System: " . $e->getMessage();
    exit;
}

// CLEAN UP SESSION (JANGAN HAPUS NIM AGAR TIDAK LOGOUT)
unset($_SESSION['permasalahan']);
unset($_SESSION['jawaban']);
session_regenerate_id(true);
?>