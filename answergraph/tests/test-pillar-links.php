<?php
require_once __DIR__ . '/runner.php';

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/../');
if (file_exists(__DIR__ . '/../includes/class-answergraph-pillar-links.php')) {
    require_once __DIR__ . '/../includes/class-answergraph-pillar-links.php';
}

class TestAnswerGraphPillarLinks extends AnswerGraphTestCase {

    public function test_finds_unlinked_pillar_anchor_and_ignores_existing_links_or_headings() {
        if (!class_exists('AnswerGraph_Pillar_Links')) {
            throw new Exception('Class AnswerGraph_Pillar_Links not found.');
        }
        $pl    = new AnswerGraph_Pillar_Links();
        $rules = $pl->parse_rules("sertifikasi kompetensi SMK2 => https://dantaramandiri.co.id/bimtek-smk2\ninvalid line\nbad scheme => httpfoo://example.com\nxss => javascript:alert(1)");
        $this->assertEquals(1, count($rules));

        $html_linked      = '<p>Ikuti <a href="https://dantaramandiri.co.id/bimtek-smk2">sertifikasi kompetensi SMK2</a>.</p>';
        $html_rel_linked  = '<p>Lihat <a href="/bimtek-smk2">halaman utama</a> sebelum mengikuti sertifikasi kompetensi SMK2.</p>';
        $html_prefix_diff = '<p>Baca <a href="https://dantaramandiri.co.id/bimtek-smk2-faq">FAQ</a> lalu daftar sertifikasi kompetensi SMK2.</p>';
        $html_code        = '<pre><code>sertifikasi kompetensi SMK2</code></pre>';
        $html_heading     = '<h2>sertifikasi kompetensi SMK2</h2><p>Artikel ini membahas audit.</p>';
        $html_opportunity = '<p>Perusahaan wajib mengikuti sertifikasi kompetensi SMK2 sebelum audit.</p>';

        $this->assertEquals(0, count($pl->find_opportunities($html_linked, 'https://dantaramandiri.co.id/artikel-1.html', $rules)));
        $this->assertEquals(0, count($pl->find_opportunities($html_rel_linked, 'https://dantaramandiri.co.id/artikel-1b.html', $rules)));
        $this->assertEquals(1, count($pl->find_opportunities($html_prefix_diff, 'https://dantaramandiri.co.id/artikel-1c.html', $rules)), 'Prefix-only different URL must not block suggestion');
        $this->assertEquals(0, count($pl->find_opportunities($html_code, 'https://dantaramandiri.co.id/artikel-1d.html', $rules)));
        $this->assertEquals(0, count($pl->find_opportunities($html_heading, 'https://dantaramandiri.co.id/artikel-2.html', $rules)));
        $this->assertEquals(0, count($pl->find_opportunities($html_opportunity, 'https://dantaramandiri.co.id/bimtek-smk2/', $rules)), 'Must skip self-URL');

        $opps = $pl->find_opportunities($html_opportunity, 'https://dantaramandiri.co.id/artikel-3.html', $rules);
        $this->assertEquals(1, count($opps));
        $this->assertEquals('https://dantaramandiri.co.id/bimtek-smk2', $opps[0]['url']);
    }
}

run_test_file(__FILE__);
