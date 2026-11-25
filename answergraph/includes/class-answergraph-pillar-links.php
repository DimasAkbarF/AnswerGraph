<?php
/**
 * Pillar Internal Link Opportunity Suggester
 * Detects unlinked target anchors outside existing links, headings, and code blocks.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AnswerGraph_Pillar_Links {

    const DEFAULT_RULES = "sertifikasi kompetensi SMK2 => https://dantaramandiri.co.id/bimtek-smk2\nkonsultan SMK2 pembangkit listrik => https://dantaramandiri.co.id/konsultan-smk2-pembangkit-listrik.html";

    /**
     * Parses "anchor => https://..." lines into structured rule pairs.
     */
    public function parse_rules($raw_lines) {
        $lines = preg_split('/\r\n|\r|\n/', (string) $raw_lines);
        $rules = [];
        foreach ($lines as $line) {
            $parts = explode('=>', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $anchor = trim($parts[0]);
            $url    = trim($parts[1]);
            if ($anchor === '' || !preg_match('#^https?://#i', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            $rules[] = [
                'anchor' => $anchor,
                'url'    => $url,
            ];
        }
        return $rules;
    }

    /**
     * Normalizes an internal/external URL for exact target comparison without prefix collisions.
     */
    protected function normalize_url_target($url) {
        $u = strtolower(trim((string) $url));
        $u = preg_replace('/#.*$/', '', $u);
        return rtrim((string) $u, '/');
    }

    /**
     * Finds anchor keywords that appear in body text without already linking to target URL.
     */
    public function find_opportunities($html, $current_url, array $rules) {
        $raw = (string) $html;
        if (trim($raw) === '' || empty($rules)) {
            return [];
        }

        $curr_norm = $this->normalize_url_target($current_url);
        $linked_targets = [];
        if (preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\']/i', $raw, $hrefs)) {
            foreach ($hrefs[1] as $href) {
                $norm = $this->normalize_url_target($href);
                $linked_targets[$norm] = true;
                $path = parse_url($norm, PHP_URL_PATH);
                if (is_string($path) && $path !== '') {
                    $linked_targets[rtrim($path, '/')] = true;
                }
            }
        }

        // Exclude anchors, headings, and code blocks from anchor matching
        $scrubbed = preg_replace('/<(a|h[1-6]|code|pre)\b[^>]*>[\s\S]*?<\/\1>/i', ' ', $raw);
        if ($scrubbed === null) {
            $scrubbed = $raw;
        }
        $body_text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $scrubbed)));

        $opportunities = [];
        foreach ($rules as $rule) {
            $target_norm = $this->normalize_url_target($rule['url']);
            $target_path = rtrim((string) parse_url($target_norm, PHP_URL_PATH), '/');
            if ($curr_norm !== '' && ($curr_norm === $target_norm || ($target_path !== '' && $curr_norm === $target_path))) {
                continue;
            }
            if (isset($linked_targets[$target_norm]) || ($target_path !== '' && isset($linked_targets[$target_path]))) {
                continue;
            }
            if (preg_match('/\b' . preg_quote($rule['anchor'], '/') . '\b/iu', $body_text)) {
                $opportunities[] = [
                    'anchor'  => $rule['anchor'],
                    'url'     => $rule['url'],
                    'snippet' => '<a href="' . htmlspecialchars($rule['url'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($rule['anchor'], ENT_QUOTES, 'UTF-8') . '</a>',
                ];
            }
        }
        return $opportunities;
    }
}
