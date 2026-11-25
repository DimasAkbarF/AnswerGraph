<?php
/**
 * Dantara ESDM Regulatory & Anti-AI-Slop Linter
 * Detects common legal misattributions, Permen ESDM 10/2021 errors, and banned AI phrases.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AnswerGraph_Compliance_Linter {

    /**
     * Evaluates HTML content for regulatory accuracy & editorial quality.
     *
     * @param string $html
     * @param string $slug Current post slug (used to skip self-link check on /bimtek-smk2).
     * @return array
     */
    public function lint($html, $slug = '') {
        $raw  = (string) $html;
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($raw)));
        $issues = [];

        if ($text === '') {
            return [
                'issues'       => [],
                'issues_count' => 0,
                'status'       => 'clean',
            ];
        }

        // 1. Salah kutip Pasal 4 sebagai dasar ambang batas 5 MW / 200 kVA (seharusnya Pasal 26 & Pasal 28)
        if (preg_match('/\bPasal\s+4\b/i', $text) && preg_match('/(SMK2|5\s*MW|200\s*kVA)/i', $text)) {
            $issues[] = [
                'code'     => 'wrong_pasal_4',
                'severity' => 'error',
                'message'  => 'Menyebut "Pasal 4" untuk kewajiban/ambang SMK2. Pasal 4 hanya membagi jenis instalasi; gunakan Pasal 26 ayat (1) dan Pasal 28 ayat (2) Permen ESDM No. 10/2021.',
            ];
        }

        // 2. Salah ambang batas "> 5 MW" / "di atas 5 MW" (seharusnya "paling kecil 5 MW" / ">= 5 MW")
        if (preg_match('/(di\s+atas\s+5\s*MW|lebih\s+dari\s+5\s*MW|>\s*5\s*MW|&gt;\s*5\s*MW)/i', $raw)) {
            $issues[] = [
                'code'     => 'wrong_5mw_threshold',
                'severity' => 'error',
                'message'  => 'Menulis "> 5 MW" atau "di atas/lebih dari 5 MW". Sesuai Pasal 28 ayat (2), ambang wajib adalah "paling kecil 5 MW (>= 5 MW)" termasuk tepat 5 MW.',
            ];
        }

        // 3. Salah masa berlaku SKTTK 5 tahun (seharusnya tepat 3 tahun)
        if (preg_match('/SKTTK[^.]{0,80}\b5\s*(?:\(lima\)\s*)?tahun\b|\b5\s*(?:\(lima\)\s*)?tahun\b[^.]{0,80}SKTTK/iu', $text)) {
            $issues[] = [
                'code'     => 'wrong_skttk_validity',
                'severity' => 'error',
                'message'  => 'Menyebut masa berlaku SKTTK 5 tahun. Masa berlaku SKTTK resmi adalah 3 (tiga) tahun.',
            ];
        }

        // 4. Mencampuradukkan dasar hukum SMK2 dengan Permenaker
        if (preg_match('/SMK2[^.]{0,120}Permenaker|Permenaker[^.]{0,120}(?:mengatur|mewajibkan|dasar)\s+SMK2/iu', $text)) {
            $issues[] = [
                'code'     => 'permenaker_smk2',
                'severity' => 'error',
                'message'  => 'Mengaitkan SMK2 dengan Permenaker. SMK2 berada mutlak di bawah Permen ESDM No. 10/2021 (Kemenaker hanya menaungi SMK3).',
            ];
        }

        // 5. Salah atribusi LSSM-067-IDN tanpa menyebut LSSM penerbit sertifikat
        if (stripos($text, 'LSSM-067-IDN') !== false && !preg_match('/(LSSM\s+terakreditasi\s+KAN|Evodia)/i', $text)) {
            $issues[] = [
                'code'     => 'misattributed_lssm',
                'severity' => 'warning',
                'message'  => 'LSSM-067-IDN adalah akreditasi KAN milik LSSM penerbit sertifikat ISO 9001:2015 (PT Evodia Global Sertifikasi), bukan nomor akreditasi milik Dantara. Gunakan redaksi "tersertifikasi ISO 9001:2015 oleh LSSM terakreditasi KAN (LSSM-067-IDN)".',
            ];
        }

        // 6. Klaim "Inspektur Ketenagalistrikan" sebagai tenaga ahli internal
        if (preg_match('/(didukung|tenaga\s+ahli|instruktur)[^.]{0,80}Inspektur\s+Ketenagalistrikan/iu', $text)) {
            $issues[] = [
                'code'     => 'inspector_conflict_claim',
                'severity' => 'warning',
                'message'  => 'Mengklaim "Inspektur Ketenagalistrikan" sebagai tenaga ahli/instruktur berisiko menimbulkan persepsi konflik kepentingan.',
            ];
        }

        // 7. Frasa klise AI terlarang
        $cliches = [
            'di era digital yang serba dinamis',
            'tidak dapat dipungkiri bahwa',
            'solusi mutakhir',
            'secara keseluruhan, dapat disimpulkan',
            'secara keseluruhan, dapat ditarik kesimpulan',
        ];
        foreach ($cliches as $phrase) {
            if (stripos($text, $phrase) !== false) {
                $issues[] = [
                    'code'     => 'ai_cliche',
                    'severity' => 'warning',
                    'message'  => 'Terdeteksi frasa klise AI ("' . $phrase . '"). Ganti dengan kalimat teknis spesifik.',
                ];
                break;
            }
        }

        // 8. Cek tautan internal ke halaman pilar /bimtek-smk2
        if ($slug !== 'bimtek-smk2' && preg_match('/\b(SMK2|SKTTK|PJK2)\b/i', $text) && stripos($raw, '/bimtek-smk2') === false) {
            $issues[] = [
                'code'     => 'missing_pillar_link',
                'severity' => 'info',
                'message'  => 'Belum ada internal link kontekstual ke halaman pilar /bimtek-smk2 (anchor: "sertifikasi kompetensi SMK2").',
            ];
        }

        $has_error = false;
        foreach ($issues as $issue) {
            if ($issue['severity'] === 'error') {
                $has_error = true;
                break;
            }
        }

        return [
            'issues'       => $issues,
            'issues_count' => count($issues),
            'status'       => $has_error ? 'error' : (count($issues) > 0 ? 'warning' : 'clean'),
        ];
    }
}
