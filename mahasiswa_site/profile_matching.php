<?php

// Fungsi Normalisasi Skor
function konversiSkor($skor) {
    if ($skor <= 10) return 1;
    if ($skor <= 15) return 2;
    if ($skor <= 20) return 3;
    if ($skor <= 25) return 4;
    return 5;
}

// Fungsi Bobot
function bobotGap($gap) {
    if ($gap == 0) return 5;
    if ($gap == 1) return 4.5;
    if ($gap == 2) return 3.5;
    if ($gap == 3) return 2.5;
    if ($gap == 4) return 1.5;
    if ($gap == -1) return 4;
    if ($gap == -2) return 3;
    if ($gap == -3) return 2;
    if ($gap == -4) return 1;
    return 1; 
}

// --- FUNGSI UTAMA SPK ---
function hitungSPKProfileMatching($koneksi, $id_jurusan, $skor_mahasiswa) {
    
    $factorMap = [
        'pk_autonomy'    => 'pk_autonomy',
        'pk_security'    => 'pk_security',
        'pk_tf'          => 'pk_tf',
        'pk_gm'          => 'pk_gm',
        'pk_ec'          => 'pk_ec',
        'pk_service'     => 'pk_service',
        'pk_challenge'   => 'pk_challenge',
        'pk_lifestyle'   => 'pk_lifestyle'
    ];

    $query = mysqli_query($koneksi, "SELECT * FROM profil_pekerjaan WHERE id_jurusan = '$id_jurusan'");
    
    // Cek Error Query 
    if (!$query) {
        die("Query Error di Profile Matching: " . mysqli_error($koneksi));
    }

    $ranking = [];

    while ($p = mysqli_fetch_assoc($query)) {
        
        // Tentukan Core Factor (CF) & Secondary Factor (SF) ---
        $temp_standar = [];
        foreach ($factorMap as $key => $colDb) {
            $temp_standar[$key] = $p[$colDb];
        }
        arsort($temp_standar); 
        $core_keys = array_slice(array_keys($temp_standar), 0, 3); 

        $cf = []; 
        $sf = [];

        // Perhitungan Gap & Bobot ---
        foreach ($factorMap as $keySkor => $colDb) {
            
            $rawUser    = isset($skor_mahasiswa[$keySkor]) ? $skor_mahasiswa[$keySkor] : 0;
            $rawStandar = $p[$colDb]; 

            $nilaiUserNormal    = konversiSkor($rawUser);
            $nilaiStandarNormal = konversiSkor($rawStandar); 

            $gap = $nilaiUserNormal - $nilaiStandarNormal;
            $bobot = bobotGap($gap);

            if (in_array($keySkor, $core_keys)) {
                $cf[] = $bobot;
            } else {
                $sf[] = $bobot;
            }
        }

        // Hitung Nilai Akhir ---
        $ncf = (count($cf) > 0) ? array_sum($cf) / count($cf) : 0;
        $nsf = (count($sf) > 0) ? array_sum($sf) / count($sf) : 0;
        
        $nilai_akhir = (0.6 * $ncf) + (0.4 * $nsf);

        $ranking[] = [
            'pekerjaan' => $p['nama_pekerjaan'],
            'nilai'     => $nilai_akhir
        ];
    }
    
    usort($ranking, fn($a, $b) => $b['nilai'] <=> $a['nilai']);
    
    return array_slice($ranking, 0, 3);
}
?>