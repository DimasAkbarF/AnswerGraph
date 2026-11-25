<?php
require_once __DIR__ . '/runner.php';

if (file_exists(__DIR__ . '/../includes/class-answergraph-geo-analyzer.php')) {
    require_once __DIR__ . '/../includes/class-answergraph-geo-analyzer.php';
}

class TestAnswerGraphGEOAnalyzer extends AnswerGraphTestCase {
    public function test_detects_numeric_statistics_and_quotes() {
        if (!class_exists('AnswerGraph_GEO_Analyzer')) {
            throw new Exception("Class AnswerGraph_GEO_Analyzer not found.");
        }

        $analyzer = new AnswerGraph_GEO_Analyzer();
        
        // 1. Content with high factual density (passes thresholds)
        $good_content = '<p>Berdasarkan data Kemenaker, sebanyak 85% perusahaan telah mengintegrasikan K2 dengan efisiensi meningkat 10x lipat dalam 3 tahun terakhir.</p><blockquote>"Kepatuhan adalah kunci utama keselamatan industri," ungkap Ir. Budi Hartono.</blockquote><p>Pelajari lebih lanjut di <a href="https://esdm.go.id">Portal Resmi ESDM</a>.</p>';
        $eval = $analyzer->evaluate_content($good_content);

        $this->assertGreaterThanOrEqual(2, $eval['stats_count'], "Should detect numeric stats/percentages");
        $this->assertGreaterThanOrEqual(1, $eval['quotes_count'], "Should detect blockquote/attributed quotes");
        $this->assertGreaterThanOrEqual(1, $eval['external_citations_count'], "Should detect external authority link");
        $this->assertGreaterThan(70, $eval['geo_score'], "Score should exceed 70 for highly cited structure");
        
        // 2. Vague content lacking stats/quotes (should trigger recommendations)
        $vague_content = '<p>Banyak perusahaan kelistrikan yang belum patuh regulasi keselamatan dan ini sangat berbahaya.</p>';
        $eval_vague = $analyzer->evaluate_content($vague_content);
        $this->assertNotEmpty($eval_vague['recommendations'], "Should provide actionable tips for vague content");
        $this->assertStringContainsString("statistik", $eval_vague['recommendations'][0]);
    }
}

run_test_file(__FILE__);
