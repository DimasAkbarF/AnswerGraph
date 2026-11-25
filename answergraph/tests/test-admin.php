<?php
require_once __DIR__ . '/runner.php';

// Mock WordPress functions if not present
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
    function register_setting($group, $name) {}
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
    function settings_fields($group) { echo "<input type='hidden' name='group' value='$group'>"; }
}
if (!function_exists('submit_button')) {
    function submit_button($text = 'Save', $type = 'primary') { echo "<button type='submit'>$text</button>"; }
}
if (!function_exists('checked')) {
    function checked($val, $current) { if ($val === $current) echo "checked='checked'"; }
}
if (!function_exists('esc_url')) {
    function esc_url($url) { return htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); }
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
if (!function_exists('current_user_can')) {
    function current_user_can($cap) { return true; }
}
if (!function_exists('wp_die')) {
    function wp_die($msg) { throw new Exception($msg); }
}
if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}
if (!function_exists('get_post_meta')) {
    function get_post_meta($id, $key, $single = false) { return ''; }
}
if (!function_exists('get_permalink')) {
    function get_permalink($id = 0) { return 'https://dantaramandiri.co.id/panduan-siujang'; }
}
if (!function_exists('add_query_arg')) {
    function add_query_arg($key, $val, $url) { return $url . '?' . $key . '=' . $val; }
}
if (!function_exists('admin_url')) {
    function admin_url($path = '') { return 'https://dantaramandiri.co.id/wp-admin/' . ltrim($path, '/'); }
}
if (!function_exists('wp_nonce_url')) {
    function wp_nonce_url($url, $action = -1) { return $url . '&_wpnonce=test'; }
}
if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action = -1, $name = '_wpnonce') { echo "<input type='hidden' name='$name' value='test'>"; }
}
if (!function_exists('get_posts')) {
    function get_posts($args = []) { return []; }
}

require_once __DIR__ . '/../includes/class-answergraph-geo-analyzer.php';
require_once __DIR__ . '/../includes/class-answergraph-aeo-analyzer.php';
require_once __DIR__ . '/../includes/class-answergraph-compliance-linter.php';
require_once __DIR__ . '/../admin/class-answergraph-admin.php';

class TestAnswerGraphAdmin extends AnswerGraphTestCase {

    public function test_admin_initialization() {
        $admin = new AnswerGraph_Admin();
        $this->assertTrue(is_object($admin), 'AnswerGraph_Admin instantiated');
    }

    public function test_render_settings_page() {
        $admin = new AnswerGraph_Admin();
        ob_start();
        $admin->render_settings_page();
        $output = ob_get_clean();

        $this->assertStringContainsString('AnswerGraph - Modern AEO &amp; GEO Engine', $output);
        $this->assertStringContainsString('Auto-Update Plugin 1-Klik', $output);
        $this->assertStringContainsString('Entity Disambiguation', $output);
        $this->assertStringContainsString('robots.txt', $output);
        $this->assertStringContainsString('LLMs.txt &amp; LLMs-full.txt Manager', $output);
    }

    public function test_render_audit_page() {
        $admin = new AnswerGraph_Admin();
        ob_start();
        $admin->render_audit_page();
        $output = ob_get_clean();

        $this->assertStringContainsString('Audit Semua Artikel', $output);
    }

    public function test_render_post_metabox() {
        $admin = new AnswerGraph_Admin();
        $post = (object) [
            'ID' => 123,
            'post_title' => 'Panduan Siujang Gatrik ESDM',
            'post_content' => '
                <h2>Apa itu Siujang Gatrik ESDM?</h2>
                <p>Siujang Gatrik adalah sistem informasi terintegrasi milik Ditjen Ketenagalistrikan ESDM yang digunakan untuk memproses permohonan sertifikasi laik operasi dan sertifikat kompetensi tenaga teknik ketenagalistrikan (SKTTK) secara online di seluruh Indonesia.</p>
                <p>Menurut regulasi ESDM No 12 Tahun 2021: "Setiap tenaga teknik wajib memiliki SKTTK aktif."</p>
                <p>Sebanyak 95% permohonan disetujui lebih cepat melalui LSK resmi. Biaya uji kompetensi berkisar Rp 2.500.000 hingga Rp 5.000.000 tergantung level kualifikasi.</p>
                <ol>
                    <li>Login ke portal Siujang Gatrik ESDM</li>
                    <li>Pilih LSK PT Dantara Ryaber Mandiri</li>
                </ol>
                <p>Kunjungi website resmi di <a href="https://siujang.esdm.go.id/">siujang.esdm.go.id</a> untuk memulai.</p>
            '
        ];

        ob_start();
        $admin->render_post_metabox($post);
        $output = ob_get_clean();

        // Checks for Yoast-like GUI elements
        $this->assertStringContainsString('GEO Citation Score', $output);
        $this->assertStringContainsString('AEO Direct Answer Box', $output);
        $this->assertStringContainsString('Checklist Analisis Mesin Penjawab AI', $output);
        $this->assertStringContainsString('Data Statistik &amp; Angka Spesifik', $output);
        $this->assertStringContainsString('Kutipan Ahli / Regulasi Resmi', $output);
        $this->assertStringContainsString('format=llm-md', $output);
        $this->assertStringContainsString('answergraphRunLiveAnalysis', $output);
        $this->assertStringContainsString('data-ag="geo-score-num"', $output);
        $this->assertStringContainsString('data-ag-elementor=', $output);
        $this->assertStringContainsString('if (html === null) return;', $output);
        $this->assertStringContainsString('Linter Regulasi ESDM', $output);
        $this->assertStringContainsString('answergraph_schema_type', $output);
    }

    public function test_elementor_content_extraction() {
        $admin = new AnswerGraph_Admin();
        $elementor_json = json_encode([
            [
                'elements' => [
                    [
                        'settings' => [
                            'heading_title' => 'Apa itu Bimtek SMK2 Ketenagalistrikan?',
                            'editor' => 'Bimtek SMK2 adalah pelatihan sistem manajemen keselamatan ketenagalistrikan resmi yang diselenggarakan oleh LSK Dantara Mandiri untuk memastikan ketaatan regulasi ESDM.',
                        ]
                    ]
                ]
            ]
        ]);

        $reflection = new ReflectionClass($admin);
        $method = $reflection->getMethod('extract_elementor_strings');
        $extracted = $method->invoke($admin, json_decode($elementor_json, true));

        $this->assertStringContainsString('Bimtek SMK2', $extracted);
        $this->assertStringContainsString('LSK Dantara Mandiri', $extracted);
    }
}

run_test_file(__FILE__);
