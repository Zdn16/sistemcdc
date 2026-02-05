<?php
// Tampilkan semua error PHP agar terlihat di layar (Matikan saat production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('session.gc_maxlifetime', 3600); // 1 jam
session_set_cookie_params(3600);

session_start();

// CEK AKTIVITAS SESSION
if (!isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
} elseif (time() - $_SESSION['LAST_ACTIVITY'] > 3600) {
    session_unset();
    session_destroy();
    die("Session habis, silakan ulangi asesmen dari awal.");
}

$_SESSION['LAST_ACTIVITY'] = time();

include "koneksi.php";
include "profile_matching.php";

// LOAD PHPMAILER
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// FUNGSI UNTUK MENGHUBUNGI FLASK
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

// VALIDASI WAJIB
$missing = [];
if (!isset($_SESSION['nim'])) $missing[] = 'Session NIM';
if (!isset($_SESSION['nama'])) $missing[] = 'Session Nama';
if (!isset($_SESSION['jk'])) $missing[] = 'Session Jenis Kelamin';
if (!isset($_SESSION['tanggal_lahir'])) $missing[] = 'Session Tanggal Lahir';
if (!isset($_SESSION['no_hp'])) $missing[] = 'Session No HP';
if (!isset($_SESSION['email'])) $missing[] = 'Session Email';
if (!isset($_SESSION['id_fakultas'])) $missing[] = 'Session ID Fakultas';
if (!isset($_SESSION['id_jurusan'])) $missing[] = 'Session ID Jurusan';
if (!isset($_SESSION['permasalahan'])) $missing[] = 'Session Permasalahan';
if (empty($_POST['jawaban'])) $missing[] = 'Post Jawaban (Form tidak terisi)';

if (!empty($missing)) {
    echo "<h3>Data berikut tidak ditemukan:</h3><ul>";
    foreach ($missing as $m) { echo "<li>$m</li>"; }
    echo "</ul><br>Silakan ulangi pengisian dari tahap awal.";
    die();
}

// AMBIL DATA SESSION 
$nim          = mysqli_real_escape_string($koneksi, $_SESSION['nim']);
$nama         = mysqli_real_escape_string($koneksi, $_SESSION['nama']);
$jk           = mysqli_real_escape_string($koneksi, $_SESSION['jk']);
$tgl_lahir    = mysqli_real_escape_string($koneksi, $_SESSION['tanggal_lahir']);
$no_hp        = mysqli_real_escape_string($koneksi, $_SESSION['no_hp']);
$email        = mysqli_real_escape_string($koneksi, $_SESSION['email']);
$id_fakultas  = (int)$_SESSION['id_fakultas']; 
$id_jurusan   = (int)$_SESSION['id_jurusan'];
$angkatan     = mysqli_real_escape_string($koneksi, $_SESSION['angkatan']);
$permasalahan = mysqli_real_escape_string($koneksi, $_SESSION['permasalahan']);

date_default_timezone_set('Asia/Jakarta');
$tanggal = date("Y-m-d H:i:s");

// Ambil nama fakultas & jurusan
$stmt = mysqli_prepare($koneksi, "
    SELECT f.nama_fakultas, j.nama_jurusan
    FROM fakultas f
    JOIN jurusan j ON j.id_fakultas = f.id_fakultas
    WHERE f.id_fakultas = ? AND j.id_jurusan = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $id_fakultas, $id_jurusan);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);

if (!$row) {
    die("Fakultas/Jurusan tidak valid (tidak ditemukan di DB)");
}

$fakultas = $row['nama_fakultas'];
$jurusan  = $row['nama_jurusan'];

// PANGGIL ML SEBELUM MASUK DATABASE

$kategori_hasil_prediksi = "Manual Check"; 
try {
    $kategori_hasil_prediksi = cek_kategori_ke_flask($permasalahan);
} catch (Exception $e) {
    $kategori_hasil_prediksi = "Error Koneksi AI";
}

// TRANSAKSI DB
mysqli_begin_transaction($koneksi);

try {

    if (empty($_SESSION['nim'])) {
        throw new Exception("Session habis, silakan isi ulang asesmen");
    }

    /* INSERT ATAU UPDATE MAHASISWA */
    $cek_mhs = mysqli_query($koneksi, "SELECT nim FROM mahasiswa WHERE nim = '$nim'");

    if (mysqli_num_rows($cek_mhs) > 0) {
        // === UPDATE ===
        mysqli_query($koneksi, "
            INSERT INTO mahasiswa 
            (nim, nama, jenis_kelamin, tanggal_lahir, no_hp, email, id_jurusan, angkatan)
            VALUES 
            ('$nim','$nama','$jk','$tgl_lahir','$no_hp','$email','$id_jurusan','$angkatan')
            ON DUPLICATE KEY UPDATE
                nama = VALUES(nama),
                jenis_kelamin = VALUES(jenis_kelamin),
                tanggal_lahir = VALUES(tanggal_lahir),
                no_hp = VALUES(no_hp),
                email = VALUES(email),
                id_jurusan = VALUES(id_jurusan),
                angkatan = VALUES(angkatan)
        ");

    } else {
        // === INSERT ===
        $insert = mysqli_query($koneksi, "
            INSERT INTO mahasiswa
            (nim, nama, jenis_kelamin, tanggal_lahir, no_hp, email, id_jurusan, angkatan)
            VALUES
            ('$nim','$nama','$jk','$tgl_lahir','$no_hp','$email','$id_jurusan','$angkatan')
        ");
        
        if (!$insert) throw new Exception("Gagal simpan mahasiswa: " . mysqli_error($koneksi));
    }

    /* INSERT ASESMEN */
    $result = mysqli_query($koneksi, "
        INSERT INTO asesmen (nim, tanggal_asesmen, permasalahan, kategori_permasalahan)
        VALUES ('$nim', '$tanggal', '$permasalahan', '$kategori_hasil_prediksi')
    ");

    if (!$result) throw new Exception("Insert asesmen gagal: " . mysqli_error($koneksi));

    $id_asesmen = mysqli_insert_id($koneksi);
    if ($id_asesmen <= 0) throw new Exception("ID asesmen tidak terbentuk");

    /* INSERT JAWABAN */
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

    /* UPDATE TOTAL SKOR PER KATEGORI DI TABEL ASESMEN */
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

    // Simpan Hasil COI Dominan ke Tabel Asesmen
    $hasil_asesmen = array_keys($total_per_kategori, max($total_per_kategori))[0];
    
    // Update HANYA hasil_asesmen (Label COI)
    mysqli_query($koneksi, "
        UPDATE asesmen SET hasil_asesmen = '$hasil_asesmen'
        WHERE id_asesmen = $id_asesmen
    ");

    // Hitung SPK Profile Matching (Mendapatkan Array: Pekerjaan & Nilai)
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

    // Gunakan $id_jurusan (yang diambil dari session di atas), JANGAN gunakan $jurusan (string nama)
    $rekomendasi = hitungSPKProfileMatching($koneksi, $id_jurusan, $skor_mahasiswa);
    
    // Simpan ke Tabel `hasil_rekomendasi` (Maksimal Top 3)
    $urutan = 1;
    foreach ($rekomendasi as $data_job) {
        if ($urutan > 3) break; 

        // Ambil data nama dan nilai dari array
        $nama_pekerjaan = $data_job['pekerjaan'];
        $skor_akhir     = $data_job['nilai']; 

        // Kita perlu ID Pekerjaan berdasarkan Namanya
        $q_job = mysqli_query($koneksi, "SELECT id_pekerjaan FROM profil_pekerjaan WHERE nama_pekerjaan = '$nama_pekerjaan' LIMIT 1");
        $d_job = mysqli_fetch_assoc($q_job);

        if ($d_job) {
            $id_pekerjaan_db = $d_job['id_pekerjaan'];
            
            // Masukkan ke tabel baru dengan SKOR YANG BENAR
            $query_insert_rek = "INSERT INTO hasil_rekomendasi (id_asesmen, id_pekerjaan, urutan_rekomendasi, hasil_skor) 
                                 VALUES ('$id_asesmen', '$id_pekerjaan_db', '$urutan', '$skor_akhir')";
            
            mysqli_query($koneksi, $query_insert_rek);
            $urutan++;
        }
    }

    // Variabel String untuk Email (Ambil nama pekerjaan saja dari array rekomendasi)
    $list_nama_pekerjaan = array_column(array_slice($rekomendasi, 0, 3), 'pekerjaan');
    $kesesuaian_karier   = implode(', ', $list_nama_pekerjaan); 

    /* COMMIT TRANSAKSI */
    mysqli_commit($koneksi);

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    
    // TAMPILKAN ERROR LENGKAP UNTUK DEBUGGING
    echo "<div style='font-family:monospace; background:#ffebee; color:#c62828; padding:20px; border:1px solid red; margin:20px;'>";
    echo "<h3>⛔ Transaksi Database Gagal!</h3>";
    echo "<strong>Pesan Exception:</strong> " . $e->getMessage() . "<br><br>";
    echo "<strong>Error MySQL Asli:</strong> " . mysqli_error($koneksi) . "<br>";
    echo "</div>";
    exit;
}

/* ===========================================================
   BLOK EMAIL 
   =========================================================== */
try {
    $kamus_anchor = [
        'tf' => [
            'judul_lengkap' => 'Technical Functional',
            'deskripsi'     => 'Anda memiliki bakat dan minat yang kuat pada bidang teknis atau fungsional tertentu.',
            'alasan_karir'  => 'Rekomendasi di atas dipilih karena membutuhkan spesialisasi mendalam.'
        ],
        'gm' => [
            'judul_lengkap' => 'General Managerial Competence',
            'deskripsi'     => 'Anda memiliki ambisi untuk memimpin dan mengelola orang lain.',
            'alasan_karir'  => 'Pekerjaan ini membutuhkan kemampuan leadership dan manajemen tim.'
        ],
        'autonomy' => [
            'judul_lengkap' => 'Autonomy/Independence',
            'deskripsi'     => 'Anda mendambakan kebebasan dalam bekerja dan minim supervisi ketat.',
            'alasan_karir'  => 'Profesi ini memberikan fleksibilitas tinggi sesuai kebutuhan Anda.'
        ],
        'security' => [
            'judul_lengkap' => 'Security/Stability',
            'deskripsi'     => 'Anda mengutamakan rasa aman, kepastian masa depan, dan stabilitas finansial.',
            'alasan_karir'  => 'Pekerjaan ini menawarkan jenjang karier jelas dan risiko rendah.'
        ],
        'ec' => [
            'judul_lengkap' => 'Entrepreneurial Creativity',
            'deskripsi'     => 'Anda memiliki dorongan kuat untuk menciptakan sesuatu yang baru dan berani mengambil risiko.',
            'alasan_karir'  => 'Bidang ini menuntut inovasi dan kemampuan membangun dari nol.'
        ],
        'service' => [
            'judul_lengkap' => 'Service Dedication to a Cause',
            'deskripsi'     => 'Anda ingin memberikan dampak positif bagi orang lain atau lingkungan.',
            'alasan_karir'  => 'Karier ini berorientasi pada pelayanan masyarakat.'
        ],
        'challenge' => [
            'judul_lengkap' => 'Pure Challenge',
            'deskripsi'     => 'Anda menyukai tantangan yang dianggap mustahil oleh orang lain.',
            'alasan_karir'  => 'Posisi ini penuh dengan target tinggi dan kompetisi.'
        ],
        'lifestyle' => [
            'judul_lengkap' => 'Lifestyle',
            'deskripsi'     => 'Anda ingin keseimbangan antara pekerjaan, keluarga, dan kehidupan pribadi.',
            'alasan_karir'  => 'Pekerjaan ini memungkinkan work-life balance yang baik.'
        ]
    ];

    if (isset($kamus_anchor[$hasil_asesmen])) {
        $info = $kamus_anchor[$hasil_asesmen];
    } else {
        $info = [
            'judul_lengkap' => strtoupper($hasil_asesmen),
            'deskripsi'     => 'Deskripsi detail belum tersedia.',
            'alasan_karir'  => 'Rekomendasi didasarkan pada kecocokan profil data.'
        ];
    }
    
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0;               
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
    
// Helper (ANTI REDECLARE)
if (!function_exists('renderCOIBarChartEmail')) {
    function renderCOIBarChartEmail($scores) {
        $min = 5;  $max = 30;  $range = $max - $min;

        $wrapStyle = "background:#f9f9f9;padding:15px;border-left:5px solid #2c3e50;margin-bottom:18px;border-radius:8px;";
        $titleStyle = "margin:0 0 10px 0;font-size:14px;color:#2c3e50;";
        $tableStyle = "width:100%;border-collapse:collapse;";
        $nameStyle  = "font-size:12px;color:#2c3e50;padding:6px 8px 6px 0;white-space:nowrap;vertical-align:middle;";
        $barCellStyle = "width:100%;padding:6px 0;vertical-align:middle;";
        $barBgStyle = "background:#e6e6e6;border-radius:999px;height:12px;overflow:hidden;";
        $valStyle = "font-size:12px;color:#2c3e50;padding-left:8px;white-space:nowrap;vertical-align:middle;";

        $labels = [
            "Autonomy/Independence" => "Autonomy",
            "Security/Stability" => "Security",
            "Technical Functional" => "Technical",
            "General Managerial" => "Management",
            "Entrepreneurial Creativity" => "Entrepreneur",
            "Service/Dedication" => "Service",
            "Pure Challenge" => "Challenge",
            "Lifestyle" => "Lifestyle",
        ];

        $html = "<div style='{$wrapStyle}'>";
        $html .= "<h4 style='{$titleStyle}'>Skor Hasil Asesmen</h4>";
        $html .= "<table style='{$tableStyle}'>";

        foreach ($scores as $k => $v) {
            $v = (int)$v;
            $pct = (($v - $min) / $range) * 100;
            if ($pct < 0) $pct = 0;
            if ($pct > 100) $pct = 100;

            $label = $labels[$k] ?? $k;

            $html .= "<tr>";
            $html .= "<td style='{$nameStyle}'><strong>{$label}</strong></td>";
            $html .= "<td style='{$barCellStyle}'>
                        <div style='{$barBgStyle}'>
                          <div style='width:{$pct}%;background:#2980b9;height:12px;'></div>
                        </div>
                      </td>";
            $html .= "<td style='{$valStyle}'><strong>{$v}</strong></td>";
            $html .= "</tr>";
        }

        $html .= "</table>";
        $html .= "</div>";
        return $html;
    }
}
if (!function_exists('renderAnchorExplanation')) {
    function renderAnchorExplanation() {
        $anchors = [
            'Autonomy / Independence' => 'Kebutuhan akan kebebasan dalam bekerja, fleksibilitas, dan minim kontrol atasan.',
            'Security / Stability' => 'Mengutamakan kestabilan kerja, kepastian masa depan, dan keamanan finansial.',
            'Technical / Functional' => 'Fokus pada keahlian teknis atau bidang spesifik dan ingin menjadi spesialis.',
            'General Managerial' => 'Dorongan kuat untuk memimpin, mengelola orang, dan mengambil keputusan strategis.',
            'Entrepreneurial Creativity' => 'Keinginan menciptakan usaha atau inovasi baru serta berani mengambil risiko.',
            'Service / Dedication to a Cause' => 'Motivasi bekerja untuk memberi manfaat sosial dan membantu orang lain.',
            'Pure Challenge' => 'Menyukai tantangan sulit, kompetisi, dan target yang menuntut kemampuan tinggi.',
            'Lifestyle' => 'Mengutamakan keseimbangan antara karier, keluarga, dan kehidupan pribadi.'
        ];

        $html = "
        <div style='background:#f4f6f8;padding:15px;border-radius:10px;margin-top:18px;'>
            <h4 style='margin:0 0 10px 0;font-size:14px;'>Penjelasan Singkat Career Anchor</h4>
            <ul style='margin:0;padding-left:18px;font-size:13px;line-height:1.6;'>
        ";

        foreach ($anchors as $judul => $desk) {
            $html .= "<li><strong>{$judul}:</strong> {$desk}</li>";
        }

        $html .= "
            </ul>
        </div>";

        return $html;
    }
}

if (!function_exists('renderTopJobsEmail')) {
    function renderTopJobsEmail($topJobs) {
        $html = "";
        $cardStyle = "background:#ffffff;border:1px solid #e6e6e6;border-radius:10px;padding:12px 14px;margin:10px 0;";
        $titleStyle = "margin:0;font-size:14px;color:#2c3e50;";
        $scoreStyle = "margin:6px 0 0 0;font-size:12px;color:#666;";
        $descStyle  = "margin:8px 0 0 0;font-size:13px;color:#444;line-height:1.5;";

        $rank = 1;
        foreach ($topJobs as $job) {
            $nama = htmlspecialchars($job['nama_pekerjaan'] ?? '-', ENT_QUOTES);
            $ket  = htmlspecialchars($job['ket_pekerjaan'] ?? '', ENT_QUOTES);
            $skor = isset($job['hasil_skor']) ? (float)$job['hasil_skor'] : null;

            $html .= "<div style='{$cardStyle}'>";
            $html .= "<p style='{$titleStyle}'><strong>{$rank} {$nama}</strong></p>";

            if ($skor !== null) {
                $html .= "<p style='{$scoreStyle}'>Skor kesesuaian: <strong>" . number_format($skor, 3) . "</strong></p>";
            }

            if (!empty($ket)) {
                $html .= "<p style='{$descStyle}'>{$ket}</p>";
            }

            $html .= "</div>";
            $rank++;
        }
        return $html;
    }
}

// Data COI untuk grafik 
$coiScores = [
    "Autonomy/Independence"      => $total_per_kategori['autonomy'],
    "Security/Stability"         => $total_per_kategori['security'],
    "Technical Functional"       => $total_per_kategori['tf'],
    "General Managerial"         => $total_per_kategori['gm'],
    "Entrepreneurial Creativity" => $total_per_kategori['ec'],
    "Service/Dedication"         => $total_per_kategori['service'],
    "Pure Challenge"             => $total_per_kategori['challenge'],
    "Lifestyle"                  => $total_per_kategori['lifestyle'],
];

$chartCOIHtml = renderCOIBarChartEmail($coiScores);

// Ambil Top 3 rekomendasi dari DB + ket_pekerjaan
$topJobs = [];
$qTop = mysqli_query($koneksi, "
    SELECT p.nama_pekerjaan, p.ket_pekerjaan, hr.hasil_skor
    FROM hasil_rekomendasi hr
    JOIN profil_pekerjaan p ON p.id_pekerjaan = hr.id_pekerjaan
    WHERE hr.id_asesmen = '$id_asesmen'
    ORDER BY hr.urutan_rekomendasi ASC
    LIMIT 3
");
while ($r = mysqli_fetch_assoc($qTop)) {
    $topJobs[] = $r;
}
$topJobsHtml = renderTopJobsEmail($topJobs);
$anchorExplanationHtml = renderAnchorExplanation();

// ISI EMAIL
$mail->Body = "
<div style='font-family: Arial, Helvetica, sans-serif; color:#2c3e50; line-height:1.5; max-width:700px;'>
  
  <div style='background:#2c3e50;color:#fff;padding:18px 20px;border-radius:10px;'>
    <h2 style='margin:0;font-size:18px;'>Hasil Asesmen Karier CDC Unand</h2>
    <p style='margin:6px 0 0 0;font-size:13px;opacity:0.9;'>
      Nama: <strong>{$nama}</strong> &nbsp;|&nbsp; NIM: <strong>{$nim}</strong>
    </p>
    <p style='margin:4px 0 0 0;font-size:13px;opacity:0.9;'>
      Fakultas: <strong>{$fakultas}</strong> &nbsp;|&nbsp; Jurusan: <strong>{$jurusan}</strong>
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
      <strong>Info Konseling:</strong> Jadwal konseling akan dihubungi admin melalui WhatsApp yang Anda inputkan.
      <br>Jika belum dihubungi, silakan DM Instagram <strong>@konselingkarirunand</strong>.
    </p>
  </div>

  <p style='margin-top:16px;font-size:13px;color:#666;'>
    Salam,<br><strong>CDC Unand</strong>
  </p>

</div>
";

    $mail->send();
    
    echo "<script>
        alert('Selamat! Asesmen selesai. Hasil telah dikirim ke email Anda.');
        window.location.href = 'opening.php';
    </script>";
    exit; 

} catch (Exception $e) {
    echo "<script>
        alert('Data tersimpan, namun GAGAL mengirim email. Error: " . addslashes($mail->ErrorInfo) . "');
        window.location.href = 'opening.php';
    </script>";
    exit;
}

// CLEAR SESSION (WAJIB)
unset($_SESSION['nim']);
unset($_SESSION['permasalahan']);
unset($_SESSION['jawaban']);   // kalau ada
session_regenerate_id(true);
?>