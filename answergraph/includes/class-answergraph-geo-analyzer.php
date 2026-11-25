<?php
/**
 * Princeton University Empirical GEO Analyzer
 * Based on "GEO: Generative Engine Optimization" (KDD 2024 / arXiv:2311.09735)
 * Analyzes content for statistics, expert quotation addition, and factual citations.
 */

class AnswerGraph_GEO_Analyzer {

    /**
     * Evaluates content based on Princeton GEO heuristics
     *
     * @param string $html
     * @param string $site_domain
     * @return array
     */
    public function evaluate_content($html, $site_domain = '') {
        $stats_count = $this->count_statistics($html);
        $quotes_count = $this->count_quotes($html);
        $external_citations_count = $this->count_external_links($html, $site_domain);

        // Compute GEO Score (0 - 100)
        // Princeton paper findings: Statistics + Quotations provide +30-40% citation lift
        $stats_score = min(40, $stats_count * 15);
        $quote_score = min(35, $quotes_count * 20);
        $cit_score   = min(25, $external_citations_count * 15);

        $geo_score = $stats_score + $quote_score + $cit_score;

        $recommendations = [];
        if ($stats_count < 2) {
            $recommendations[] = 'Tambahkan minimal 2 data statistik/angka spesifik (misal persentase, rasio, atau angka terukur) untuk meningkatkan kredibilitas sitasi AI.';
        }
        if ($quotes_count < 1) {
            $recommendations[] = 'Tambahkan kutipan ahli industri atau pernyataan otoritatif (blockquote/tanda kutip) untuk memperkuat atribusi AI.';
        }
        if ($external_citations_count < 1) {
            $recommendations[] = 'Sematkan tautan rujukan resmi (regulasi, jurnal, atau instansi pemerintah) sebagai dasar klaim fakta.';
        }

        return [
            'stats_count'              => $stats_count,
            'quotes_count'             => $quotes_count,
            'external_citations_count' => $external_citations_count,
            'geo_score'                => $geo_score,
            'recommendations'          => $recommendations,
        ];
    }

    /**
     * Detects statistical markers: percentages, multipliers (10x), year ranges, and metric numbers
     */
    protected function count_statistics($html) {
        $text = strip_tags($html);
        $patterns = [
            '/\b\d+([.,]\d+)?\s*%/i',                                // Percentages (85%, 12.5%)
            '/\b\d+\s*x\s*(lipat)?\b/i',                              // Multipliers (10x, 2x lipat)
            '/\b(Rp|IDR|\$)\s*[\d.,]+/i',                             // Currency values
            '/\b\d+\s*(tahun|bulan|hari|peserta|perusahaan|ribu|juta|miliar)\b/i', // Quantified metrics
        ];

        $matches_total = 0;
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $matches_total += count($matches[0]);
            }
        }
        return $matches_total;
    }

    /**
     * Detects quotations: blockquote tags or quotes with attribution
     */
    protected function count_quotes($html) {
        $count = 0;
        // 1. Blockquote tags
        if (preg_match_all('/<blockquote\b[^>]*>(.*?)<\/blockquote>/is', $html, $b_matches)) {
            $count += count($b_matches[0]);
        }
        // 2. Inline quotes with attribution
        $text = strip_tags($html);
        if (preg_match_all('/["“](.*?)["”]\s*(?:ujar|kata|ungkap|jelas|tutur|sebut|menurut|tegas)/iu', $text, $q_matches)) {
            $count += count($q_matches[0]);
        }
        return $count;
    }

    /**
     * Detects external citation links
     */
    protected function count_external_links($html, $site_domain = '') {
        if (!preg_match_all('/<a\b[^>]*href=["\']([^"\']*)["\'][^>]*>/is', $html, $matches)) {
            return 0;
        }

        $external = 0;
        $domain = $site_domain ?: (function_exists('home_url') ? parse_url(home_url(), PHP_URL_HOST) : '');

        foreach ($matches[1] as $url) {
            if (strpos($url, 'http') === 0) {
                $host = parse_url($url, PHP_URL_HOST);
                if ($host && (!$domain || stripos($host, $domain) === false)) {
                    $external++;
                }
            }
        }
        return $external;
    }
}
