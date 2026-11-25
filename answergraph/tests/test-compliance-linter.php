<?php
require_once __DIR__ . '/runner.php';

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/../');
require_once __DIR__ . '/../includes/class-answergraph-compliance-linter.php';

class TestAnswerGraphComplianceLinter extends AnswerGraphTestCase {

    public function test_flags_regulatory_and_cliche_errors() {
        $linter = new AnswerGraph_Compliance_Linter();
        $badHtml = '<p>Di era digital yang serba dinamis, Pasal 4 Permen ESDM 10/2021 mewajibkan pembangkit di atas 5 MW menerapkan SMK2. Masa berlaku SKTTK adalah 5 tahun. Akreditasi KAN LSSM-067-IDN.</p>';
        $res = $linter->lint($badHtml, 'contoh-artikel');

        $this->assertEquals('error', $res['status']);
        $codes = array_column($res['issues'], 'code');
        $this->assertTrue(in_array('wrong_pasal_4', $codes, true), 'Flags Pasal 4');
        $this->assertTrue(in_array('wrong_5mw_threshold', $codes, true), 'Flags > 5 MW');
        $this->assertTrue(in_array('wrong_skttk_validity', $codes, true), 'Flags 5 tahun SKTTK');
        $this->assertTrue(in_array('misattributed_lssm', $codes, true), 'Flags LSSM-067-IDN');
        $this->assertTrue(in_array('ai_cliche', $codes, true), 'Flags AI cliche');
        $this->assertTrue(in_array('missing_pillar_link', $codes, true), 'Flags missing /bimtek-smk2 link');
    }

    public function test_passes_compliant_article() {
        $linter = new AnswerGraph_Compliance_Linter();
        $goodHtml = '<p>Berdasarkan Pasal 26 ayat (1) dan Pasal 28 ayat (2) Permen ESDM No. 10/2021, pembangkit berkapasitas paling kecil 5 MW wajib menerapkan SMK2 dan ikut <a href="https://dantaramandiri.co.id/bimtek-smk2">sertifikasi kompetensi SMK2</a>. Masa berlaku SKTTK adalah 3 tahun. Sistem mutu tersertifikasi ISO 9001:2015 oleh LSSM terakreditasi KAN (LSSM-067-IDN).</p>';
        $res = $linter->lint($goodHtml, 'contoh-artikel');

        $this->assertEquals('clean', $res['status']);
        $this->assertEquals(0, $res['issues_count']);
    }
}

run_test_file(__FILE__);
