<?php
require_once __DIR__ . '/runner.php';

if (file_exists(__DIR__ . '/../includes/class-answergraph-aeo-analyzer.php')) {
    require_once __DIR__ . '/../includes/class-answergraph-aeo-analyzer.php';
}

class TestAnswerGraphAEOAnalyzer extends AnswerGraphTestCase {
    public function test_flags_subheading_with_optimal_direct_answer_length() {
        if (!class_exists('AnswerGraph_AEO_Analyzer')) {
            throw new Exception("Class AnswerGraph_AEO_Analyzer not found.");
        }

        $analyzer = new AnswerGraph_AEO_Analyzer();
        
        // 45-word concise answer directly following an H2 question
        $answer = "Sertifikat Kompetensi Tenaga Teknik Ketenagalistrikan atau SKTTK adalah bukti pengakuan formal resmi dari Kementerian ESDM terhadap kompetensi tenaga kerja di bidang ketenagalistrikan. Dokumen ini wajib dimiliki oleh setiap tenaga teknik instalasi dan inspeksi guna memenuhi kepatuhan regulasi keselamatan nasional dan perlindungan hukum perusahaan.";
        $word_count = count(explode(' ', $answer)); // ~39 words, let's make it 45
        $padding = " Sertifikasi ini berlaku tiga tahun.";
        $full_answer = $answer . $padding;
        $actual_count = count(explode(' ', trim($full_answer)));

        $html = "<h2>Apa itu SKTTK Ketenagalistrikan?</h2><p>{$full_answer}</p><table><tr><th>Skema</th></tr><tr><td>Pembangkitan</td></tr></table>";
        
        $res = $analyzer->analyze_snippet_potential($html);

        $this->assertTrue($res['has_direct_answer_box'], "Should detect answer box between 40-60 words");
        $this->assertTrue($res['has_data_table'], "Should detect data table presence");
        $this->assertGreaterThanOrEqual(1, $res['qa_pairs_count']);
        $this->assertEquals('green', $res['status']);
    }
}

run_test_file(__FILE__);
