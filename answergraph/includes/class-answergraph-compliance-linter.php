<?php
/**
 * Configurable Regulatory & Anti-AI-Slop Linter + Content Freshness Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class AnswerGraph_Compliance_Linter {

    const STALE_DAYS = 180;

    /**
     * Evaluates HTML content for regulatory accuracy & editorial quality.
     *
     * @param string $html
     * @param string $slug Current post slug.
     * @param array  $options ['preset_esdm' => bool, 'custom_banned' => string[]].
     * @return array
     */
    public function lint($html, $slug = '', array $options = []) {
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

        $preset_esdm = isset($options['preset_esdm'])
            ? (bool) $options['preset_esdm']
            : (!function_exists('get_option') || get_option('answergraph_linter_preset_esdm', 'yes') === 'yes');

        if ($preset_esdm) {
            if (preg_match('/\bPasal\s+4\b/i', $text) && preg_match('/(SMK2|5\s*MW|200\s*kVA)/i', $text)) {
                $issues[] = [
                    'code'     => 'wrong_pasal_4',
                    'severity' => 'error',
                    'message'  => 'Menyebut "Pasal 4" untuk kewajiban/ambang SMK2. Pasal 4 hanya membagi jenis instalasi; gunakan Pasal 26 ayat (1) dan Pasal 28 ayat (2) Permen ESDM No. 10/2021.',
                ];
            }

            if (preg_match('/(di\s+atas\s+5\s*MW|lebih\s+dari\s+5\s*MW|>\s*5\s*MW|&gt;\s*5\s*MW)/i', $raw)) {
                $issues[] = [
                    'code'     => 'wrong_5mw_threshold',
                    'severity' => 'error',
                    'message'  => 'Menulis "> 5 MW" atau "di atas/lebih dari 5 MW". Sesuai Pasal 28 ayat (2), ambang wajib adalah "paling kecil 5 MW (>= 5 MW)" termasuk tepat 5 MW.',
                ];
            }

            if (preg_match('/SKTTK[^.]{0,80}\b5\s*(?:\(lima\)\s*)?tahun\b|\b5\s*(?:\(lima\)\s*)?tahun\b[^.]{0,80}SKTTK/iu', $text)) {
                $issues[] = [
                    'code'     => 'wrong_skttk_validity',
                    'severity' => 'error',
                    'message'  => 'Menyebut masa berlaku SKTTK 5 tahun. Masa berlaku SKTTK resmi adalah 3 (tiga) tahun.',
                ];
            }

            if (preg_match('/SMK2[^.]{0,120}Permenaker|Permenaker[^.]{0,120}(?:mengatur|mewajibkan|dasar)\s+SMK2/iu', $text)) {
                $issues[] = [
                    'code'     => 'permenaker_smk2',
                    'severity' => 'error',
                    'message'  => 'Mengaitkan SMK2 dengan Permenaker. SMK2 berada mutlak di bawah Permen ESDM No. 10/2021 (Kemenaker hanya menaungi SMK3).',
                ];
            }

            if (stripos($text, 'LSSM-067-IDN') !== false && !preg_match('/(LSSM\s+terakreditasi\s+KAN|Evodia)/i', $text)) {
                $issues[] = [
                    'code'     => 'misattributed_lssm',
                    'severity' => 'warning',
                    'message'  => 'LSSM-067-IDN adalah akreditasi KAN milik LSSM penerbit sertifikat ISO 9001:2015 (PT Evodia Global Sertifikasi), bukan nomor akreditasi milik Dantara.',
                ];
            }

            if (preg_match('/(didukung|tenaga\s+ahli|instruktur)[^.]{0,80}Inspektur\s+Ketenagalistrikan/iu', $text)) {
                $issues[] = [
                    'code'     => 'inspector_conflict_claim',
                    'severity' => 'warning',
                    'message'  => 'Mengklaim "Inspektur Ketenagalistrikan" sebagai tenaga ahli/instruktur berisiko menimbulkan persepsi konflik kepentingan.',
                ];
            }

            if ($slug !== 'bimtek-smk2' && preg_match('/\b(SMK2|SKTTK|PJK2)\b/i', $text) && stripos($raw, '/bimtek-smk2') === false) {
                $issues[] = [
                    'code'     => 'missing_pillar_link',
                    'severity' => 'info',
                    'message'  => 'Belum ada internal link kontekstual ke halaman pilar /bimtek-smk2 (anchor: "sertifikasi kompetensi SMK2").',
                ];
            }
        }

        // Generic AI clichés
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

        // Custom user-configured banned terms
        $custom_terms = isset($options['custom_banned']) && is_array($options['custom_banned'])
            ? $options['custom_banned']
            : $this->get_configured_banned_terms();

        foreach ($custom_terms as $term) {
            $term = trim((string) $term);
            if ($term !== '' && stripos($text, $term) !== false) {
                $issues[] = [
                    'code'     => 'custom_banned_term',
                    'severity' => 'warning',
                    'message'  => 'Terdeteksi istilah terlarang kustom: "' . $term . '".',
                ];
            }
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

    public function get_configured_banned_terms() {
        $raw = function_exists('get_option') ? (string) get_option('answergraph_custom_banned_terms', '') : '';
        if (trim($raw) === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw))));
    }

    /**
     * Checks whether a post's modified GMT timestamp is stale (> 180 days).
     */
    public function evaluate_freshness($modified_gmt, $now_ts = 0) {
        $str = trim((string) $modified_gmt);
        if ($str === '' || strpos($str, '0000-00-00') === 0) {
            return ['days_old' => null, 'status' => 'unknown'];
        }
        $ts = strtotime($str . ' UTC');
        if ($ts === false || $ts <= 0) {
            return ['days_old' => null, 'status' => 'unknown'];
        }
        $now  = $now_ts > 0 ? (int) $now_ts : time();
        $days = (int) floor(max(0, $now - $ts) / 86400);
        return [
            'days_old' => $days,
            'status'   => $days > self::STALE_DAYS ? 'stale' : 'fresh',
        ];
    }
}
