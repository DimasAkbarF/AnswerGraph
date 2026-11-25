<?php
require_once __DIR__ . '/runner.php';

if (file_exists(__DIR__ . '/../includes/class-answergraph-llmstxt.php')) {
    require_once __DIR__ . '/../includes/class-answergraph-llmstxt.php';
}

class TestAnswerGraphLLMsTxt extends AnswerGraphTestCase {
    public function test_generates_standard_llms_txt_header_and_sections() {
        if (!class_exists('AnswerGraph_LLMsTxt')) {
            throw new Exception("Class AnswerGraph_LLMsTxt not found.");
        }

        $items = [
            [
                'title'       => 'Bimtek SMK2 ESDM',
                'url'         => 'https://dantaramandiri.co.id/bimtek-smk2',
                'description' => 'Program sertifikasi keselamatan ketenagalistrikan resmi ESDM.',
                'section'     => 'Layanan Utama',
            ],
            [
                'title'       => 'Panduan Siujang Gatrik',
                'url'         => 'https://dantaramandiri.co.id/aplikasi-siujang-gatrik.html',
                'description' => 'Alur registrasi, login, dan syarat SKTTK.',
                'section'     => 'Panduan Teknis',
            ]
        ];

        $generator = new AnswerGraph_LLMsTxt('PT Dantara Ryaber Mandiri', 'Lembaga Sertifikasi Kompetensi Resmi Ditjen Gatrik ESDM RI');
        $output = $generator->render_summary($items);

        $this->assertStringContainsString('# PT Dantara Ryaber Mandiri', $output);
        $this->assertStringContainsString('> Lembaga Sertifikasi Kompetensi Resmi Ditjen Gatrik ESDM RI', $output);
        $this->assertStringContainsString('## Layanan Utama', $output);
        $this->assertStringContainsString('- [Bimtek SMK2 ESDM](https://dantaramandiri.co.id/bimtek-smk2): Program sertifikasi keselamatan ketenagalistrikan resmi ESDM.', $output);
        $this->assertStringContainsString('## Panduan Teknis', $output);
    }
}

run_test_file(__FILE__);
