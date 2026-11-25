<?php
/**
 * Answer Engine Optimization (AEO) Analyzer
 * Optimizes content for Google Featured Snippets ("Position 0") & AI Overviews.
 * Implements the Inverted Pyramid Direct Answer Framework (40-60 words).
 */

class AnswerGraph_AEO_Analyzer {

    /**
     * Common Question / Long-Tail Query Triggers
     */
    protected $question_triggers = [
        'apa', 'apakah', 'bagaimana', 'mengapa', 'kenapa',
        'syarat', 'biaya', 'cara', 'aturan', 'kriteria', 'daftar',
        'what', 'how', 'why', 'who', 'requirements', 'process'
    ];

    /**
     * Analyzes content for AEO snippet readiness
     *
     * @param string $html
     * @return array
     */
    public function analyze_snippet_potential($html) {
        $has_direct_answer = false;
        $qa_pairs_count = 0;
        $answer_word_counts = [];

        // Match H2/H3 headings and the immediately following paragraph
        $pattern = '/<(h[23])\b[^>]*>(.*?)<\/\1>\s*<p\b[^>]*>(.*?)<\/p>/is';
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $heading_text = strtolower(strip_tags($match[2]));
                $paragraph_text = strip_tags($match[3]);

                // Check if heading is a question or procedural intent
                if ($this->is_question_heading($heading_text)) {
                    $qa_pairs_count++;
                    $word_count = count(preg_split('/\s+/u', trim($paragraph_text)));
                    $answer_word_counts[] = $word_count;

                    // Optimal Featured Snippet / AI Overview sweet spot is 40 - 60 words
                    if ($word_count >= 35 && $word_count <= 65) {
                        $has_direct_answer = true;
                    }
                }
            }
        }

        // Check for HTML tables
        $has_data_table = (bool) preg_match('/<table\b[^>]*>/i', $html);

        // Check for ordered lists
        $has_ordered_list = (bool) preg_match('/<ol\b[^>]*>/i', $html);

        // Compute overall status
        $status = ($has_direct_answer && ($has_data_table || $has_ordered_list)) ? 'green' : ($has_direct_answer ? 'orange' : 'red');

        return [
            'has_direct_answer_box' => $has_direct_answer,
            'qa_pairs_count'        => $qa_pairs_count,
            'has_data_table'        => $has_data_table,
            'has_ordered_list'      => $has_ordered_list,
            'answer_word_counts'    => $answer_word_counts,
            'status'                => $status,
        ];
    }

    /**
     * Checks if heading contains a trigger keyword
     */
    protected function is_question_heading($text) {
        foreach ($this->question_triggers as $trigger) {
            if (preg_match('/\b' . preg_quote($trigger, '/') . '\b/i', $text)) {
                return true;
            }
        }
        return false;
    }
}
