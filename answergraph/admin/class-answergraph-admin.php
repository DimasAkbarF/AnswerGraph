<?php
/**
 * AnswerGraph WordPress Admin GUI, Meta Box, Bulk Audit & LLMs Sync
 */

if (!defined('ABSPATH')) {
    exit;
}

class AnswerGraph_Admin {

    protected $geo_analyzer;
    protected $aeo_analyzer;
    protected $linter;

    public function __construct() {
        $this->geo_analyzer = new AnswerGraph_GEO_Analyzer();
        $this->aeo_analyzer = new AnswerGraph_AEO_Analyzer();
        $this->linter       = class_exists('AnswerGraph_Compliance_Linter') ? new AnswerGraph_Compliance_Linter() : null;

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('add_meta_boxes', [$this, 'add_post_metabox']);
        add_action('save_post', [$this, 'save_post_meta']);
        add_action('admin_post_answergraph_sync_llms', [$this, 'handle_sync_llms']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'AnswerGraph - AEO & GEO Engine',
            'AnswerGraph AI',
            'manage_options',
            'answergraph-settings',
            [$this, 'render_settings_page'],
            'dashicons-share-alt',
            80
        );

        if (function_exists('add_submenu_page')) {
            add_submenu_page(
                'answergraph-settings',
                'Pengaturan AnswerGraph AI',
                'Pengaturan',
                'manage_options',
                'answergraph-settings',
                [$this, 'render_settings_page']
            );
            add_submenu_page(
                'answergraph-settings',
                'Audit Semua Artikel - AnswerGraph',
                'Audit Semua Artikel',
                'manage_options',
                'answergraph-audit',
                [$this, 'render_audit_page']
            );
        }
    }

    public static function sanitize_yes_no($val) {
        return $val === 'yes' ? 'yes' : 'no';
    }

    public static function sanitize_multiline_raw($val) {
        return trim(str_replace("\0", '', (string) $val));
    }

    public function register_settings() {
        $text_opts = [
            'answergraph_sameas_urls'    => 'sanitize_textarea_field',
            'answergraph_update_url'     => 'esc_url_raw',
            'answergraph_update_token'   => 'sanitize_text_field',
            'answergraph_author_override'=> 'sanitize_text_field',
        ];
        foreach ($text_opts as $opt => $cb) {
            register_setting('answergraph_options_group', $opt, [
                'type'              => 'string',
                'sanitize_callback' => $cb,
                'default'           => $opt === 'answergraph_update_url' ? 'https://github.com/DimasAkbarF/AnswerGraph' : '',
            ]);
        }

        $toggle_opts = [
            'answergraph_allow_search_bots'   => 'yes',
            'answergraph_allow_training_bots' => 'yes',
            'answergraph_block_scrapers'      => 'yes',
            'answergraph_ssr_lang_id'         => 'yes',
            'answergraph_ssr_counter_fix'     => 'yes',
            'answergraph_ssr_h1_promote'      => 'yes',
        ];
        foreach ($toggle_opts as $opt => $default) {
            register_setting('answergraph_options_group', $opt, [
                'type'              => 'string',
                'sanitize_callback' => [__CLASS__, 'sanitize_yes_no'],
                'default'           => $default,
            ]);
        }

        foreach (['answergraph_llms_txt_content', 'answergraph_llms_full_txt_content'] as $llms_opt) {
            register_setting('answergraph_llms_group', $llms_opt, [
                'type'              => 'string',
                'sanitize_callback' => [__CLASS__, 'sanitize_multiline_raw'],
                'default'           => '',
            ]);
        }
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Anda tidak memiliki izin yang cukup untuk mengakses halaman ini.', 'answergraph'));
        }
        include ANSWERGRAPH_PATH . 'admin/views/settings-page.php';
    }

    public function render_audit_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Anda tidak memiliki izin yang cukup untuk mengakses halaman ini.', 'answergraph'));
        }
        $rows = [];
        if (function_exists('get_posts')) {
            $items = get_posts([
                'post_type'   => ['post', 'page'],
                'post_status' => 'publish',
                'numberposts' => 150,
                'orderby'     => 'modified',
                'order'       => 'DESC',
            ]);
            foreach ($items as $item) {
                $content = $this->get_combined_post_content($item);
                $geo     = $this->geo_analyzer->evaluate_content($content);
                $aeo     = $this->aeo_analyzer->analyze_snippet_potential($content);
                $lint    = $this->linter ? $this->linter->lint($content, $item->post_name) : ['issues_count' => 0, 'issues' => [], 'status' => 'clean'];
                $rows[]  = [
                    'ID'         => $item->ID,
                    'title'      => $item->post_title,
                    'type'       => $item->post_type,
                    'slug'       => $item->post_name,
                    'geo_score'  => (int) $geo['geo_score'],
                    'aeo_status' => $aeo['status'],
                    'lint'       => $lint,
                    'edit_url'   => function_exists('get_edit_post_link') ? get_edit_post_link($item->ID, 'raw') : '#',
                    'view_url'   => function_exists('get_permalink') ? get_permalink($item->ID) : '#',
                ];
            }
            usort($rows, static function ($a, $b) {
                if ($a['lint']['issues_count'] !== $b['lint']['issues_count']) {
                    return $b['lint']['issues_count'] <=> $a['lint']['issues_count'];
                }
                return $a['geo_score'] <=> $b['geo_score'];
            });
        }
        include ANSWERGRAPH_PATH . 'admin/views/audit-page.php';
    }

    public function handle_sync_llms() {
        if (!current_user_can('manage_options')) {
            wp_die('Tidak memiliki izin.', 403);
        }
        check_admin_referer('answergraph_sync_llms_nonce');

        $llms_txt      = isset($_POST['answergraph_llms_txt_content']) ? self::sanitize_multiline_raw(wp_unslash($_POST['answergraph_llms_txt_content'])) : '';
        $llms_full_txt = isset($_POST['answergraph_llms_full_txt_content']) ? self::sanitize_multiline_raw(wp_unslash($_POST['answergraph_llms_full_txt_content'])) : '';

        update_option('answergraph_llms_txt_content', $llms_txt, false);
        update_option('answergraph_llms_full_txt_content', $llms_full_txt, false);

        $errors = [];
        if ($llms_txt !== '' && @file_put_contents(ABSPATH . 'llms.txt', $llms_txt . "\n", LOCK_EX) === false) {
            $errors[] = 'llms.txt';
        }
        if ($llms_full_txt !== '' && @file_put_contents(ABSPATH . 'llms-full.txt', $llms_full_txt . "\n", LOCK_EX) === false) {
            $errors[] = 'llms-full.txt';
        }

        $status = empty($errors) ? 'ok' : 'err';
        wp_safe_redirect(admin_url('admin.php?page=answergraph-settings&llms_sync=' . $status));
        exit;
    }

    public function add_post_metabox() {
        $screens = ['post', 'page'];
        foreach ($screens as $screen) {
            add_meta_box(
                'answergraph_analysis_metabox',
                'AnswerGraph - AEO & GEO Live Analysis (ChatGPT, Perplexity & AI Overviews)',
                [$this, 'render_post_metabox'],
                $screen,
                'normal',
                'high'
            );
        }
    }

    protected function get_combined_post_content($post, &$has_elementor = false) {
        $content       = isset($post->post_content) ? (string) $post->post_content : '';
        $has_elementor = false;

        if (function_exists('get_post_meta') && isset($post->ID)) {
            $elementor_data = get_post_meta($post->ID, '_elementor_data', true);
            if (!empty($elementor_data) && is_string($elementor_data)) {
                $decoded = json_decode($elementor_data, true);
                if (is_array($decoded)) {
                    $extracted = $this->extract_elementor_strings($decoded);
                    if (trim($extracted) !== '') {
                        $has_elementor = true;
                        $content .= ' ' . $extracted;
                    }
                }
            }
        }
        return $content;
    }

    public function render_post_metabox($post) {
        if (!is_object($post) || !isset($post->ID)) {
            return;
        }

        $has_elementor = false;
        $content       = $this->get_combined_post_content($post, $has_elementor);
        $slug          = isset($post->post_name) ? (string) $post->post_name : '';

        $geo_eval  = $this->geo_analyzer->evaluate_content($content);
        $aeo_eval  = $this->aeo_analyzer->analyze_snippet_potential($content);
        $lint_eval = $this->linter ? $this->linter->lint($content, $slug) : ['issues' => [], 'issues_count' => 0, 'status' => 'clean'];

        $schema_type = function_exists('get_post_meta') ? (string) get_post_meta($post->ID, '_answergraph_schema_type', true) : 'default';
        if ($schema_type === '') {
            $schema_type = 'default';
        }

        $md_url = function_exists('get_permalink') && get_permalink($post->ID)
            ? add_query_arg('format', 'llm-md', get_permalink($post->ID))
            : '#';

        include ANSWERGRAPH_PATH . 'admin/views/meta-box.php';
    }

    public function save_post_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['answergraph_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['answergraph_meta_nonce'])), 'answergraph_save_meta')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['answergraph_schema_type'])) {
            $allowed = ['default', 'course', 'service'];
            $val     = sanitize_text_field(wp_unslash($_POST['answergraph_schema_type']));
            if (in_array($val, $allowed, true)) {
                update_post_meta($post_id, '_answergraph_schema_type', $val);
            }
        }
    }

    protected function extract_elementor_strings($elements) {
        $text = '';
        if (!is_array($elements)) {
            return '';
        }

        foreach ($elements as $el) {
            if (isset($el['settings']) && is_array($el['settings'])) {
                foreach (['title', 'editor', 'text', 'description', 'heading_title'] as $k) {
                    if (!empty($el['settings'][$k]) && is_string($el['settings'][$k])) {
                        $text .= ' ' . $el['settings'][$k];
                    }
                }
            }
            if (isset($el['elements']) && is_array($el['elements'])) {
                $text .= ' ' . $this->extract_elementor_strings($el['elements']);
            }
        }
        return $text;
    }
}
