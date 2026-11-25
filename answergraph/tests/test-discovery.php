<?php
require_once __DIR__ . '/runner.php';

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/../');
if (!defined('ANSWERGRAPH_PATH')) define('ANSWERGRAPH_PATH', __DIR__ . '/../');
if (!defined('ANSWERGRAPH_URL')) define('ANSWERGRAPH_URL', 'https://dantaramandiri.co.id/wp-content/plugins/answergraph/');
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) { return __DIR__ . '/../'; }
    function plugin_dir_url($file) { return 'https://dantaramandiri.co.id/wp-content/plugins/answergraph/'; }
    function add_action($t, $cb, $p = 10, $a = 1) {}
    function add_filter($t, $cb, $p = 10, $a = 1) {}
    function register_activation_hook($f, $cb) {}
    function register_deactivation_hook($f, $cb) {}
    function add_query_arg($k, $v, $u) { return $u . (strpos($u, '?') === false ? '?' : '&') . $k . '=' . $v; }
    function esc_url($u) { return htmlspecialchars($u, ENT_QUOTES, 'UTF-8'); }
    function get_option($k, $d = '') { return $d; }
}

require_once __DIR__ . '/../answergraph.php';

class TestAnswerGraphDiscovery extends AnswerGraphTestCase {

    public function test_renders_markdown_alternate_link_tag() {
        $core = new AnswerGraph_Core();
        if (!method_exists($core, 'render_markdown_discovery_link')) {
            throw new Exception('Method render_markdown_discovery_link not found.');
        }
        $tag = $core->render_markdown_discovery_link('https://dantaramandiri.co.id/bimtek-smk2');
        $this->assertEquals(
            '<link rel="alternate" type="text/markdown" href="https://dantaramandiri.co.id/bimtek-smk2?format=llm-md" />',
            trim($tag)
        );
    }
}

run_test_file(__FILE__);
