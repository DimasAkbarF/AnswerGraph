<?php
/**
 * Plugin Name: AnswerGraph - Modern AEO, GEO & SEO Engine
 * Plugin URI: https://github.com/DimasAkbarF/AnswerGraph
 * Update URI: https://github.com/DimasAkbarF/AnswerGraph
 * Description: Engine AEO, GEO, Entity SEO, Linter Regulasi ESDM, LLMs.txt Manager, dan Auto-Updater 1-klik untuk WordPress.
 * Version: 2.0.1
 * Requires PHP: 7.4
 * Author: Dimas Akbar Faturohman
 * Author URI: https://github.com/DimasAkbarF
 * Text Domain: answergraph
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ANSWERGRAPH_VERSION', '2.0.1');
define('ANSWERGRAPH_PATH', plugin_dir_path(__FILE__));
define('ANSWERGRAPH_URL', plugin_dir_url(__FILE__));

// Autoload components
require_once ANSWERGRAPH_PATH . 'includes/class-answergraph-robots.php';
require_once ANSWERGRAPH_PATH . 'includes/class-answergraph-llmstxt.php';
require_once ANSWERGRAPH_PATH . 'includes/class-answergraph-markdown-view.php';
require_once ANSWERGRAPH_PATH . 'includes/class-answergraph-geo-analyzer.php';
require_once ANSWERGRAPH_PATH . 'includes/class-answergraph-aeo-analyzer.php';
require_once ANSWERGRAPH_PATH . 'includes/class-answergraph-compliance-linter.php';
require_once ANSWERGRAPH_PATH . 'includes/class-answergraph-schema.php';
require_once ANSWERGRAPH_PATH . 'includes/class-answergraph-updater.php';
require_once ANSWERGRAPH_PATH . 'admin/class-answergraph-admin.php';

class AnswerGraph_Core {

    protected $robots;
    protected $llmstxt;
    protected $markdown;
    protected $schema;

    public function __construct() {
        $this->robots   = new AnswerGraph_Robots();
        $this->llmstxt  = new AnswerGraph_LLMsTxt();
        $this->markdown = new AnswerGraph_Markdown_View();
        $this->schema   = new AnswerGraph_Schema();

        // 1. Robots.txt Filter
        add_filter('robots_txt', [$this->robots, 'filter_robots'], 10, 2);

        // 2. Query Vars & Rewrite Rules
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('init', [$this, 'register_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_virtual_endpoints'], 1);

        // 3. Schema JSON-LD Output & Yoast Harmonization
        if (defined('WPSEO_VERSION')) {
            add_filter('wpseo_schema_organization', [$this, 'enrich_yoast_organization_schema']);
            add_filter('wpseo_schema_person', [$this, 'override_yoast_person_schema']);
        } else {
            add_action('wp_head', [$this, 'output_schema_graph'], 2);
        }
        add_action('wp_head', [$this, 'output_post_specific_schema'], 25);

        // 4. Author Display Override (e.g. "drm" -> Tim Asesor)
        add_filter('the_author', [$this, 'filter_author_display'], 20);
        add_filter('get_the_author_display_name', [$this, 'filter_author_display'], 20);

        // 5. Optional SSR Technical Fixes (Language id-ID, Elementor Counter 0%, Empty H1 Promotion)
        if ($this->option_enabled('answergraph_ssr_lang_id', 'yes')) {
            add_filter('language_attributes', [$this, 'force_indonesian_lang_attr'], 20);
            add_filter('wpseo_locale', static function () { return 'id_ID'; }, 20);
        }

        if ($this->option_enabled('answergraph_ssr_counter_fix', 'yes')) {
            add_filter('elementor/widget/render_content', [$this, 'fix_elementor_counter_ssr'], 20, 2);
        }

        if ($this->option_enabled('answergraph_ssr_h1_promote', 'yes')) {
            add_filter('elementor/frontend/the_content', [$this, 'ensure_single_h1_on_subpages'], 99);
            add_filter('the_content', [$this, 'ensure_single_h1_on_subpages_loop'], 99);
        }
    }

    protected function option_enabled($key, $default = 'no') {
        return function_exists('get_option') ? get_option($key, $default) === 'yes' : ($default === 'yes');
    }

    /**
     * Parses saved SameAs URLs (one per line) into a clean validated URL array.
     */
    public function get_configured_sameas_urls() {
        $raw = function_exists('get_option') ? (string) get_option('answergraph_sameas_urls', '') : '';
        if (trim($raw) === '') {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $urls  = [];
        foreach ($lines as $line) {
            $clean = trim($line);
            if ($clean === '') {
                continue;
            }
            $valid = function_exists('esc_url_raw') ? esc_url_raw($clean) : filter_var($clean, FILTER_VALIDATE_URL);
            if ($valid && strpos($valid, 'http') === 0) {
                $urls[] = $valid;
            }
        }
        return array_values(array_unique($urls));
    }

    public function register_query_vars($vars) {
        $vars[] = 'answergraph_endpoint';
        return $vars;
    }

    public function register_rewrite_rules() {
        add_rewrite_rule('^llms\.txt$', 'index.php?answergraph_endpoint=llms', 'top');
        add_rewrite_rule('^llms-full\.txt$', 'index.php?answergraph_endpoint=llms-full', 'top');
        add_rewrite_tag('%answergraph_endpoint%', '([^&]+)');
    }

    public function handle_virtual_endpoints() {
        $endpoint = get_query_var('answergraph_endpoint');
        $format   = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : '';

        if (!$endpoint && !empty($_SERVER['REQUEST_URI'])) {
            $req_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if (preg_match('#(^|/)llms\.txt$#i', $req_path)) {
                $endpoint = 'llms';
            } elseif (preg_match('#(^|/)llms-full\.txt$#i', $req_path)) {
                $endpoint = 'llms-full';
            }
        }

        if ($format === 'llm-md' && is_singular()) {
            global $post;
            if ($post && $post->post_status === 'publish') {
                if (function_exists('post_password_required') && post_password_required($post)) {
                    wp_die(__('Konten ini dilindungi kata sandi.', 'answergraph'), 403);
                }
                header('Content-Type: text/markdown; charset=utf-8');
                header('X-Robots-Tag: noindex, follow');
                echo "# {$post->post_title}\n\n";
                $raw_html = apply_filters('the_content', $post->post_content);
                echo $this->markdown->convert_html($raw_html);
                exit;
            }
        }

        if ($endpoint === 'llms') {
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Robots-Tag: noindex, follow');
            $custom = function_exists('get_option') ? trim((string) get_option('answergraph_llms_txt_content', '')) : '';
            if ($custom !== '') {
                echo $custom . "\n";
                exit;
            }
            $posts = get_posts([
                'numberposts'  => 50,
                'post_status'  => 'publish',
                'has_password' => false,
                'orderby'      => 'modified',
                'order'        => 'DESC',
            ]);
            $items = [];
            foreach ($posts as $p) {
                if (!empty($p->post_password)) {
                    continue;
                }
                $items[] = [
                    'title'       => $p->post_title,
                    'url'         => get_permalink($p->ID),
                    'description' => wp_trim_words(strip_tags($p->post_content), 20),
                    'section'     => 'Latest Updates',
                ];
            }
            echo $this->llmstxt->render_summary($items);
            exit;
        }

        if ($endpoint === 'llms-full') {
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Robots-Tag: noindex, follow');
            $custom = function_exists('get_option') ? trim((string) get_option('answergraph_llms_full_txt_content', '')) : '';
            if ($custom !== '') {
                echo $custom . "\n";
                exit;
            }
            $posts = get_posts([
                'numberposts'  => 15,
                'post_status'  => 'publish',
                'has_password' => false,
            ]);
            $docs = [];
            foreach ($posts as $p) {
                if (!empty($p->post_password)) {
                    continue;
                }
                $raw_html = apply_filters('the_content', $p->post_content);
                $docs[] = [
                    'title'   => $p->post_title,
                    'url'     => get_permalink($p->ID),
                    'content' => $this->markdown->convert_html($raw_html),
                ];
            }
            echo $this->llmstxt->render_full($docs);
            exit;
        }
    }

    public function enrich_yoast_organization_schema($data) {
        $sameas = $this->get_configured_sameas_urls();
        if (!empty($sameas)) {
            $existing = isset($data['sameAs']) && is_array($data['sameAs']) ? $data['sameAs'] : [];
            $data['sameAs'] = array_values(array_unique(array_merge($existing, $sameas)));
        }
        return $data;
    }

    public function filter_author_display($name) {
        $override = function_exists('get_option') ? trim((string) get_option('answergraph_author_override', '')) : '';
        if ($override === '') {
            return $name;
        }
        $lower = strtolower(trim((string) $name));
        if ($lower === 'drm' || $lower === 'admindrm') {
            return $override;
        }
        return $name;
    }

    public function override_yoast_person_schema($data) {
        $override = function_exists('get_option') ? trim((string) get_option('answergraph_author_override', '')) : '';
        if ($override !== '' && !empty($data['name'])) {
            $data['name'] = $this->filter_author_display((string) $data['name']);
        }
        return $data;
    }

    public function output_schema_graph() {
        if (!is_singular()) {
            return;
        }
        global $post;
        if (!$post) {
            return;
        }

        $org_data = [
            'name'   => get_bloginfo('name'),
            'url'    => home_url(),
            'logo'   => get_site_icon_url() ?: (home_url() . '/logo.png'),
            'sameAs' => $this->get_configured_sameas_urls(),
        ];

        $page_data = [
            'title'         => get_the_title($post),
            'url'           => get_permalink($post),
            'description'   => wp_strip_all_tags(get_the_excerpt($post)),
            'datePublished' => get_the_date('c', $post),
            'dateModified'  => get_the_modified_date('c', $post),
        ];

        $graph = $this->schema->generate_graph($org_data, $page_data);
        echo $this->schema->render_html($graph);
    }

    public function output_post_specific_schema() {
        if (is_admin() || !is_singular()) {
            return;
        }
        global $post;
        if (!$post || !function_exists('get_post_meta')) {
            return;
        }
        $type = get_post_meta($post->ID, '_answergraph_schema_type', true);
        if ($type !== 'course' && $type !== 'service') {
            return;
        }

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => $type === 'course' ? 'Course' : 'Service',
            'name'        => get_the_title($post),
            'description' => wp_strip_all_tags(get_the_excerpt($post)),
            'provider'    => [
                '@type' => 'Organization',
                'name'  => get_bloginfo('name'),
                'url'   => home_url('/'),
            ],
            'inLanguage'  => 'id-ID',
        ];
        if ($type === 'service') {
            $schema['areaServed'] = 'ID';
        }

        echo "<script type=\"application/ld+json\" class=\"answergraph-post-schema\">\n"
            . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)
            . "\n</script>\n";
    }

    public function force_indonesian_lang_attr($output) {
        $replaced = preg_replace('/\blang=(["\'])[^"\']*\1/i', 'lang="id-ID"', (string) $output);
        return $replaced !== null ? $replaced : $output;
    }

    public function fix_elementor_counter_ssr($content, $widget) {
        if (is_admin()) {
            return $content;
        }
        $name = method_exists($widget, 'get_name') ? $widget->get_name() : '';
        if ($name !== 'counter') {
            return $content;
        }
        return (string) preg_replace_callback(
            '/(<span\b[^>]*class="[^"]*elementor-counter-number[^"]*"[^>]*data-to-value="([^"]+)"[^>]*>)\s*0\s*(<\/span>)/i',
            static function ($m) {
                $raw = trim($m[2]);
                $val = is_numeric($raw) ? number_format((float) $raw, 0, ',', '.') : $raw;
                return $m[1] . esc_html($val) . $m[3];
            },
            $content
        );
    }

    public function ensure_single_h1_on_subpages($content) {
        if (is_admin() || !is_singular() || is_front_page() || stripos((string) $content, '<h1') !== false) {
            return $content;
        }
        $replaced = preg_replace('/<h2(\b[^>]*)>(.*?)<\/h2>/is', '<h1$1>$2</h1>', (string) $content, 1);
        return $replaced !== null ? $replaced : $content;
    }

    public function ensure_single_h1_on_subpages_loop($content) {
        if (!in_the_loop() || !is_main_query()) {
            return $content;
        }
        return $this->ensure_single_h1_on_subpages($content);
    }
}

register_activation_hook(__FILE__, function () {
    $core = new AnswerGraph_Core();
    $core->register_rewrite_rules();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

add_action('plugins_loaded', function () {
    new AnswerGraph_Core();
    if (is_admin()) {
        new AnswerGraph_Admin();
        $update_url   = function_exists('get_option') ? get_option('answergraph_update_url', 'https://github.com/DimasAkbarF/AnswerGraph') : 'https://github.com/DimasAkbarF/AnswerGraph';
        if ($update_url === '') {
            $update_url = 'https://github.com/DimasAkbarF/AnswerGraph';
        }
        $update_token = function_exists('get_option') ? get_option('answergraph_update_token', '') : '';
        new AnswerGraph_Updater(__FILE__, ANSWERGRAPH_VERSION, $update_url, $update_token);
    }
});
