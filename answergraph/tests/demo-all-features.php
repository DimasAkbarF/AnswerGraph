<?php
/**
 * Interactive Demo & Test Script for AnswerGraph Features
 */

require_once __DIR__ . '/../includes/class-answergraph-robots.php';
require_once __DIR__ . '/../includes/class-answergraph-llmstxt.php';
require_once __DIR__ . '/../includes/class-answergraph-markdown-view.php';
require_once __DIR__ . '/../includes/class-answergraph-geo-analyzer.php';
require_once __DIR__ . '/../includes/class-answergraph-aeo-analyzer.php';
require_once __DIR__ . '/../includes/class-answergraph-schema.php';

echo "=================================================================\n";
echo "           DEMO PENGUJIAN 6 FITUR UTAMA ANSWERGRAPH                 \n";
echo "=================================================================\n\n";

// -------------------------------------------------------------
// FITUR 1: Dynamic llms.txt Generator (Standar llmstxt.org)
// -------------------------------------------------------------
echo ">>> [FITUR 1] GENERATOR /llms.txt (STANDAR llmstxt.org)\n";
$llms = new AnswerGraph_LLMsTxt('PT Dantara Ryaber Mandiri', 'Lembaga Sertifikasi Kompetensi Ketenagalistrikan Resmi Ditjen Gatrik ESDM');
$mock_posts = [
    [
        'title'       => 'Bimtek & Uji Kompetensi SMK2 Ketenagalistrikan',
        'url'         => 'https://dantaramandiri.co.id/bimtek-smk2',
        'description' => 'Program sertifikasi keselamatan ketenagalistrikan resmi ESDM.',
        'section'     => 'Program Prioritas',
    ],
    [
        'title'       => 'Panduan Aplikasi Siujang Gatrik ESDM',
        'url'         => 'https://dantaramandiri.co.id/aplikasi-siujang-gatrik-milik-kementerian-esdm-republik-indonesia.html',
        'description' => 'Tutorial pendaftaran akun, alur login, dan syarat SKTTK.',
        'section'     => 'Panduan & Regulasi',
    ]
];
$llms_output = $llms->render_summary($mock_posts);
echo $llms_output . "\n";

// -------------------------------------------------------------
// FITUR 2: Token-Efficient Clean Markdown Endpoint (?format=llm-md)
// -------------------------------------------------------------
echo ">>> [FITUR 2] CLEAN MARKDOWN VIEW (?format=llm-md)\n";
$md_converter = new AnswerGraph_Markdown_View();
$sample_html = '
<header><nav>Home / Artikel / Siujang</nav></header>
<h2>Panduan Praktis Cara Login Siujang Gatrik</h2>
<p>Bagi tenaga teknik ketenagalistrikan, pastikan Anda telah menyiapkan <strong>KTP</strong> dan <a href="https://siujang.esdm.go.id">akun resmi ESDM</a>.</p>
<table>
  <tr><th>Tahap</th><th>Waktu</th><th>Output</th></tr>
  <tr><td>Pendaftaran</td><td>1 Hari</td><td>Akun Terverifikasi</td></tr>
  <tr><td>Uji LSK</td><td>3 Hari</td><td>SKTTK Terbit</td></tr>
</table>
<footer>Copyright 2026 Dantara Mandiri</footer>
<script>console.log("tracker");</script>
';
$md_result = $md_converter->convert_html($sample_html);
echo "HASIL KONVERSI HTML KE MARKDOWN BERSIH (SIAP DIKONSUMSI AI):\n";
echo "---------------------------------------------------------\n";
echo $md_result . "\n\n";

// -------------------------------------------------------------
// FITUR 3: AI Crawler & Search Grounding Gatekeeper (robots.txt)
// -------------------------------------------------------------
echo ">>> [FITUR 3] PENGONTROL BOT AI DI robots.txt\n";
$robots = new AnswerGraph_Robots();
$default_robots = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php";
$robots_result = $robots->filter_robots($default_robots, true);
echo "OUTPUT ATURAN robots.txt OTOMATIS:\n";
echo "---------------------------------------------------------\n";
echo $robots_result . "\n";

// -------------------------------------------------------------
// FITUR 4: Princeton University GEO Analyzer (Stats & Quotes)
// -------------------------------------------------------------
echo ">>> [FITUR 4] ANALYZER GEO RISET PRINCETON (Stats & Quotes)\n";
$geo_analyzer = new AnswerGraph_GEO_Analyzer();

// Kasus A: Artikel tanpa data (basa-basi)
$bad_article = "<p>Kami adalah konsultan terbaik yang berpengalaman dan sangat profesional dalam membantu perusahaan.</p>";
$eval_bad = $geo_analyzer->evaluate_content($bad_article);

// Kasus B: Artikel kaya data (ala Princeton)
$good_article = '
<p>Hingga November 2026, lebih dari 85% perusahaan pembangkit telah menerapkan SMK2 dengan penurunan insiden hingga 10x lipat dalam 3 tahun.</p>
<blockquote>"Kepatuhan regulasi bukan sekadar syarat formalitas, melainkan jaminan keandalan instalasi listrik nasional," tegas Direktur Ketenagalistrikan.</blockquote>
<p>Informasi resmi dapat divalidasi melalui <a href="https://esdm.go.id">Kementerian ESDM</a>.</p>
';
$eval_good = $geo_analyzer->evaluate_content($good_article);

echo "Contoh 1 (Artikel Tanpa Data): Skor GEO = {$eval_bad['geo_score']}/100\n";
echo "Saran Plugin: " . implode(" | ", $eval_bad['recommendations']) . "\n\n";
echo "Contoh 2 (Artikel Berbasis Fakta Princeton): Skor GEO = {$eval_good['geo_score']}/100 (Optimal Dikutip AI!)\n";
echo "Fakta terdeteksi: {$eval_good['stats_count']} statistik, {$eval_good['quotes_count']} kutipan, {$eval_good['external_citations_count']} link rujukan.\n\n";

// -------------------------------------------------------------
// FITUR 5: AEO Direct Answer Box Analyzer (40-60 kata)
// -------------------------------------------------------------
echo ">>> [FITUR 5] DETEKTOR AEO DIRECT ANSWER (Featured Snippet)\n";
$aeo_analyzer = new AnswerGraph_AEO_Analyzer();

$snippet_html = '
<h2>Apa itu SKTTK Ketenagalistrikan?</h2>
<p>Sertifikat Kompetensi Tenaga Teknik Ketenagalistrikan atau SKTTK adalah bukti pengakuan formal resmi dari Kementerian ESDM terhadap kompetensi tenaga kerja di bidang ketenagalistrikan. Dokumen ini wajib dimiliki oleh setiap tenaga teknik instalasi dan inspeksi guna memenuhi kepatuhan regulasi keselamatan nasional dan perlindungan hukum perusahaan. Sertifikasi ini berlaku tiga tahun.</p>
<table><tr><th>Level</th><th>Kualifikasi</th></tr><tr><td>Level 5</td><td>Penanggung Jawab Teknik (PJT)</td></tr></table>
';
$aeo_eval = $aeo_analyzer->analyze_snippet_potential($snippet_html);
echo "Status Kotak Jawaban: " . strtoupper($aeo_eval['status']) . "\n";
echo "Deteksi: Direct Answer Box = " . ($aeo_eval['has_direct_answer_box'] ? 'YA' : 'TIDAK') . " | Tabel Data = " . ($aeo_eval['has_data_table'] ? 'YA' : 'TIDAK') . "\n";
echo "Panjang Jawaban: {$aeo_eval['answer_word_counts'][0]} kata (Rentang Emas: 40-60 kata)\n\n";

// -------------------------------------------------------------
// FITUR 6: Semantic Entity Schema Generator (sameAs & Knowledge Graph)
// -------------------------------------------------------------
echo ">>> [FITUR 6] SEMANTIC ENTITY GRAPH (sameAs & JSON-LD)\n";
$schema = new AnswerGraph_Schema();
$org = [
    'name'   => 'PT Dantara Ryaber Mandiri',
    'url'    => 'https://dantaramandiri.co.id',
    'logo'   => 'https://dantaramandiri.co.id/logo.png',
    'sameAs' => ['https://www.wikidata.org/wiki/Q12345678', 'https://www.linkedin.com/company/dantaramandiri']
];
$page = [
    'title'         => 'Bimtek SMK2 Ketenagalistrikan Resmi ESDM',
    'url'           => 'https://dantaramandiri.co.id/bimtek-smk2',
    'description'   => 'Program sertifikasi resmi SMK2 dari LSK Dantara Mandiri.',
    'datePublished' => '2026-01-15T08:00:00+07:00',
    'dateModified'  => '2026-11-23T21:00:00+07:00'
];
$graph = $schema->generate_graph($org, $page);
echo "JSON-LD Script yang Dicetak ke <head> Website:\n";
echo "---------------------------------------------------------\n";
echo $schema->render_html($graph) . "\n";
