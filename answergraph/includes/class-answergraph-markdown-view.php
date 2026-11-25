<?php
/**
 * Clean Markdown View for AI Scrapers & Agents
 * Strips layout noise and converts semantic HTML to token-efficient Markdown.
 */

class AnswerGraph_Markdown_View {

    /**
     * Converts HTML string to clean Markdown
     *
     * @param string $html
     * @return string
     */
    public function convert_html($html) {
        // 1. Remove script, style, nav, footer, form, iframe tags and their contents
        $html = preg_replace('/<(script|style|nav|footer|header|aside|form|iframe)\b[^>]*>(.*?)<\/\1>/is', '', $html);

        // 2. Convert tables to markdown pipe table
        $html = preg_replace_callback('/<table\b[^>]*>(.*?)<\/table>/is', [$this, 'convert_table'], $html);

        // 3. Convert headings
        $html = preg_replace('/<h1\b[^>]*>(.*?)<\/h1>/is', "\n# $1\n", $html);
        $html = preg_replace('/<h2\b[^>]*>(.*?)<\/h2>/is', "\n## $1\n", $html);
        $html = preg_replace('/<h3\b[^>]*>(.*?)<\/h3>/is', "\n### $1\n", $html);
        $html = preg_replace('/<h4\b[^>]*>(.*?)<\/h4>/is', "\n#### $1\n", $html);

        // 4. Convert formatting
        $html = preg_replace('/<(strong|b)\b[^>]*>(.*?)<\/\1>/is', '**$2**', $html);
        $html = preg_replace('/<(em|i)\b[^>]*>(.*?)<\/\1>/is', '*$2*', $html);

        // 5. Convert links
        $html = preg_replace('/<a\b[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is', '[$2]($1)', $html);

        // 6. Convert lists
        $html = preg_replace('/<li\b[^>]*>(.*?)<\/li>/is', "- $1\n", $html);
        $html = preg_replace('/<\/?(ul|ol)\b[^>]*>/is', "\n", $html);

        // 7. Convert paragraphs & linebreaks
        $html = preg_replace('/<p\b[^>]*>(.*?)<\/p>/is', "$1\n\n", $html);
        $html = preg_replace('/<br\s*\/?>/is', "\n", $html);

        // 8. Strip remaining HTML tags
        $html = strip_tags($html);

        // 9. Clean up whitespace
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace("/\n{3,}/", "\n\n", $html);

        return trim($html);
    }

    /**
     * Helper to convert HTML table to Markdown pipe table
     */
    protected function convert_table($matches) {
        $table_html = $matches[1];
        preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $table_html, $tr_matches);
        if (empty($tr_matches[1])) {
            return '';
        }

        $md_rows = [];
        $header_done = false;
        $column_count = 0;

        foreach ($tr_matches[1] as $tr) {
            // Check headers
            preg_match_all('/<th\b[^>]*>(.*?)<\/th>/is', $tr, $th_matches);
            if (!empty($th_matches[1])) {
                $cells = array_map(function($c) { return trim(strip_tags($c)); }, $th_matches[1]);
                $column_count = count($cells);
                $md_rows[] = '| ' . implode(' | ', $cells) . ' |';
                $md_rows[] = '| ' . implode(' | ', array_fill(0, $column_count, '---')) . ' |';
                $header_done = true;
                continue;
            }

            // Normal cells
            preg_match_all('/<td\b[^>]*>(.*?)<\/td>/is', $tr, $td_matches);
            if (!empty($td_matches[1])) {
                $cells = array_map(function($c) { return trim(strip_tags($c)); }, $td_matches[1]);
                if (!$header_done && $column_count === 0) {
                    $column_count = count($cells);
                    $md_rows[] = '| ' . implode(' | ', $cells) . ' |';
                    $md_rows[] = '| ' . implode(' | ', array_fill(0, $column_count, '---')) . ' |';
                    $header_done = true;
                } else {
                    $md_rows[] = '| ' . implode(' | ', $cells) . ' |';
                }
            }
        }

        return "\n" . implode("\n", $md_rows) . "\n\n";
    }
}
