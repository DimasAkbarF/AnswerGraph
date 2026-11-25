<?php
require_once __DIR__ . '/runner.php';

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/../');
require_once __DIR__ . '/../includes/class-answergraph-updater.php';

class TestAnswerGraphUpdater extends AnswerGraphTestCase {

    public function test_normalizes_github_repo_url() {
        $api = AnswerGraph_Updater::normalize_endpoint_url('https://github.com/dantara/answergraph');
        $this->assertEquals('https://api.github.com/repos/dantara/answergraph/releases/latest', $api);

        $custom = AnswerGraph_Updater::normalize_endpoint_url('https://dantaramandiri.co.id/update.json');
        $this->assertEquals('https://dantaramandiri.co.id/update.json', $custom);
    }

    public function test_parses_github_release_json() {
        $gh = json_encode([
            'tag_name'     => 'v2.1.0',
            'html_url'     => 'https://github.com/dantara/answergraph/releases/tag/v2.1.0',
            'body'         => 'Perbaikan bug dan fitur baru',
            'published_at' => '2026-11-26T10:00:00Z',
            'assets'       => [
                ['name' => 'answergraph.zip', 'browser_download_url' => 'https://github.com/dantara/answergraph/releases/download/v2.1.0/answergraph.zip'],
            ],
        ]);
        $res = AnswerGraph_Updater::parse_remote_release($gh, '2.0.0');
        $this->assertTrue(is_array($res), 'Release parsed');
        $this->assertEquals('2.1.0', $res['version']);
        $this->assertTrue($res['is_newer'] === true, '2.1.0 is newer than 2.0.0');
        $this->assertStringContainsString('answergraph.zip', $res['download_url']);
    }

    public function test_parses_custom_manifest_and_detects_non_newer() {
        $manifest = json_encode([
            'version'      => '2.0.0',
            'download_url' => 'https://dantaramandiri.co.id/answergraph.zip',
            'changelog'    => 'Initial v2',
        ]);
        $res = AnswerGraph_Updater::parse_remote_release($manifest, '2.0.0');
        $this->assertTrue(is_array($res), 'Manifest parsed');
        $this->assertTrue($res['is_newer'] === false, '2.0.0 is not newer than 2.0.0');
    }
}

run_test_file(__FILE__);
