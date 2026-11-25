<?php
require_once __DIR__ . '/runner.php';

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/../');
if (!function_exists('get_option')) {
    global $mock_in_opts;
    $mock_in_opts = [];
    function get_option($key, $default = '') {
        global $mock_in_opts;
        return isset($mock_in_opts[$key]) ? $mock_in_opts[$key] : $default;
    }
    function update_option($key, $val, $autoload = null) {
        global $mock_in_opts;
        $mock_in_opts[$key] = $val;
        return true;
    }
}

if (file_exists(__DIR__ . '/../includes/class-answergraph-indexnow.php')) {
    require_once __DIR__ . '/../includes/class-answergraph-indexnow.php';
}

class TestAnswerGraphIndexNow extends AnswerGraphTestCase {

    public function test_skips_draft_or_password_posts_and_builds_valid_payload() {
        if (!class_exists('AnswerGraph_IndexNow')) {
            throw new Exception('Class AnswerGraph_IndexNow not found.');
        }
        $in    = new AnswerGraph_IndexNow();
        $draft = (object) ['post_type' => 'post', 'post_password' => ''];
        $priv  = (object) ['post_type' => 'post', 'post_password' => 'secret'];
        $pub   = (object) ['post_type' => 'post', 'post_password' => ''];
        $rev   = (object) ['post_type' => 'revision', 'post_password' => ''];

        $this->assertTrue($in->should_ping_post('draft', $draft) === false, 'Draft must not ping');
        $this->assertTrue($in->should_ping_post('publish', $priv) === false, 'Passworded post must not ping');
        $this->assertTrue($in->should_ping_post('publish', $rev) === false, 'Revision must not ping');
        $this->assertTrue($in->should_ping_post('publish', $pub) === true, 'Public published post must ping');

        $key = $in->get_or_create_key();
        $this->assertTrue((bool) preg_match('/^[a-f0-9]{32}$/i', $key), 'Key must be 32-char hex');

        $payload = $in->build_payload('dantaramandiri.co.id', $key, ['https://dantaramandiri.co.id/bimtek-smk2']);
        $this->assertEquals('dantaramandiri.co.id', $payload['host']);
        $this->assertEquals($key, $payload['key']);
        $this->assertEquals('https://dantaramandiri.co.id/' . $key . '.txt', $payload['keyLocation']);
        $this->assertEquals(1, count($payload['urlList']));

        $sub_payload = $in->build_payload('example.com', $key, ['https://example.com/blog/post-1'], 'https://example.com/blog/' . $key . '.txt');
        $this->assertEquals('https://example.com/blog/' . $key . '.txt', $sub_payload['keyLocation']);
    }
}

run_test_file(__FILE__);
