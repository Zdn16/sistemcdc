<?php
// FILE: helper_email_template.php

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

            // Mapping key dari database ke label cantik
            $keyMap = [
                'autonomy' => 'Autonomy/Independence',
                'security' => 'Security/Stability',
                'tf' => 'Technical Functional',
                'gm' => 'General Managerial',
                'ec' => 'Entrepreneurial Creativity',
                'service' => 'Service/Dedication',
                'challenge' => 'Pure Challenge',
                'lifestyle' => 'Lifestyle'
            ];
            
            $labelKey = $keyMap[$k] ?? $k; // Pakai mapping db ke full name
            $labelTampil = $labels[$labelKey] ?? $labelKey; // Pakai full name ke short name

            $html .= "<tr>";
            $html .= "<td style='{$nameStyle}'><strong>{$labelTampil}</strong></td>";
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
            $html .= "<p style='{$titleStyle}'><strong>{$rank}. {$nama}</strong></p>";

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
?>