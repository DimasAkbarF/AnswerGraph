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

    public function test_respects_disabled_esdm_preset_and_evaluates_freshness() {
        $linter = new AnswerGraph_Compliance_Linter();
        $res = $linter->lint('<p>Pasal 4 pembangkit > 5 MW.</p>', 'test', [
            'preset_esdm'   => false,
            'custom_banned' => ['harga murah meriah'],
        ]);
        $this->assertEquals('clean', $res['status']);

        $res_custom = $linter->lint('<p>Promo harga murah meriah hari ini.</p>', 'test', [
            'preset_esdm'   => false,
            'custom_banned' => ['harga murah meriah'],
        ]);
        $this->assertEquals('warning', $res_custom['status']);

        $stale = $linter->evaluate_freshness('2025-01-01 00:00:00', strtotime('2026-11-25 00:00:00'));
        $fresh = $linter->evaluate_freshness('2026-10-01 00:00:00', strtotime('2026-11-25 00:00:00'));
        $zero  = $linter->evaluate_freshness('0000-00-00 00:00:00', strtotime('2026-11-25 00:00:00'));
        $bad   = $linter->evaluate_freshness('not-a-date', strtotime('2026-11-25 00:00:00'));
        $now_boundary = strtotime('2026-11-25 00:00:00 UTC');
        $at_180 = $linter->evaluate_freshness(gmdate('Y-m-d H:i:s', $now_boundary - 180 * 86400), $now_boundary);
        $at_181 = $linter->evaluate_freshness(gmdate('Y-m-d H:i:s', $now_boundary - 181 * 86400), $now_boundary);
        $this->assertEquals('stale', $stale['status']);
        $this->assertEquals('fresh', $fresh['status']);
        $this->assertEquals('unknown', $zero['status']);
        $this->assertEquals('unknown', $bad['status']);
        $this->assertEquals('fresh', $at_180['status']);
        $this->assertEquals('stale', $at_181['status']);
    }
}

run_test_file(__FILE__);
