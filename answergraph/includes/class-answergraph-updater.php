<?php
/**
 * AnswerGraph Native WordPress 1-Click Plugin Updater
 * Supports both GitHub Releases API URLs and custom update.json manifests.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AnswerGraph_Updater {

    const CACHE_KEY = 'answergraph_update_check_cache';
    const CACHE_TTL = 43200; // 12 hours

    protected $plugin_file;
    protected $plugin_basename;
    protected $slug = 'answergraph';
    protected $version;
    protected $update_url;
    protected $token;

    public function __construct($plugin_file, $version, $update_url = '', $token = '') {
        $this->plugin_file     = $plugin_file;
        $this->plugin_basename = function_exists('plugin_basename') ? plugin_basename($plugin_file) : 'answergraph/answergraph.php';
        $this->version         = $version;
        $this->update_url      = trim((string) $update_url);
        $this->token           = trim((string) $token);

        if (function_exists('add_filter')) {
            add_filter('plugin_action_links_' . $this->plugin_basename, [$this, 'add_action_links']);
            add_action('admin_post_answergraph_check_update', [$this, 'handle_manual_check']);
        }

        if ($this->update_url !== '' && function_exists('add_filter')) {
            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
            add_filter('upgrader_source_selection', [$this, 'normalize_zip_folder'], 10, 4);
            add_filter('http_request_args', [$this, 'attach_auth_header'], 10, 2);
            add_action('upgrader_process_complete', [$this, 'purge_update_cache'], 10, 2);
        }
    }

    /**
     * Resolves user-entered github.com/owner/repo or raw endpoint into a fetchable JSON URL.
     */
    public static function normalize_endpoint_url($url) {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^https?://github\.com/([^/]+)/([^/]+?)(?:\.git|/.*)?$#i', $url, $m)) {
            return 'https://api.github.com/repos/' . $m[1] . '/' . $m[2] . '/releases/latest';
        }
        return $url;
    }

    /**
     * Parses either GitHub Releases API JSON or custom update.json manifest.
     *
     * @return array|null Normalized release array or null if invalid / not newer.
     */
    public static function parse_remote_release($raw_body, $current_version) {
        $data = json_decode((string) $raw_body, true);
        if (!is_array($data)) {
            return null;
        }

        // Format A: GitHub Releases API
        if (isset($data['tag_name'])) {
            $remote_version = ltrim((string) $data['tag_name'], "vV \t\n\r");
            $remote_version = preg_replace('/^answergraph-v?/i', '', $remote_version);
            $download_url   = '';

            if (!empty($data['assets']) && is_array($data['assets'])) {
                foreach ($data['assets'] as $asset) {
                    $name = isset($asset['name']) ? (string) $asset['name'] : '';
                    if (substr($name, -4) === '.zip') {
                        $download_url = !empty($asset['browser_download_url']) ? (string) $asset['browser_download_url'] : '';
                        if ($name === 'answergraph.zip') {
                            break;
                        }
                    }
                }
            }
            if ($download_url === '' && !empty($data['zipball_url'])) {
                $download_url = (string) $data['zipball_url'];
            }

            if ($remote_version === '' || $download_url === '') {
                return null;
            }

            return [
                'version'      => $remote_version,
                'download_url' => $download_url,
                'homepage'     => !empty($data['html_url']) ? (string) $data['html_url'] : 'https://dantaramandiri.co.id/',
                'changelog'    => !empty($data['body']) ? (string) $data['body'] : 'Pembaruan AnswerGraph.',
                'last_updated' => !empty($data['published_at']) ? (string) $data['published_at'] : '',
                'tested'       => '6.7',
                'requires_php' => '7.4',
                'is_newer'     => version_compare($remote_version, $current_version, '>'),
            ];
        }

        // Format B: Custom update.json manifest
        if (!empty($data['version']) && !empty($data['download_url'])) {
            $remote_version = ltrim((string) $data['version'], "vV \t\n\r");
            return [
                'version'      => $remote_version,
                'download_url' => (string) $data['download_url'],
                'homepage'     => !empty($data['homepage']) ? (string) $data['homepage'] : 'https://dantaramandiri.co.id/',
                'changelog'    => !empty($data['changelog']) ? (string) $data['changelog'] : 'Pembaruan AnswerGraph.',
                'last_updated' => !empty($data['last_updated']) ? (string) $data['last_updated'] : '',
                'tested'       => !empty($data['tested']) ? (string) $data['tested'] : '6.7',
                'requires_php' => !empty($data['requires_php']) ? (string) $data['requires_php'] : '7.4',
                'is_newer'     => version_compare($remote_version, $current_version, '>'),
            ];
        }

        return null;
    }

    public function get_remote_release($force_refresh = false) {
        if (!$force_refresh && function_exists('get_transient')) {
            $cached = get_transient(self::CACHE_KEY);
            if (is_array($cached) && !empty($cached['version'])) {
                $cached['is_newer'] = version_compare($cached['version'], $this->version, '>');
                return $cached;
            }
        }

        $endpoint = self::normalize_endpoint_url($this->update_url);
        if ($endpoint === '' || !function_exists('wp_remote_get')) {
            return null;
        }

        $headers = [
            'Accept'     => 'application/vnd.github+json, application/json',
            'User-Agent' => 'AnswerGraph-WordPress-Updater/' . $this->version,
        ];
        if ($this->token !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        $response = wp_remote_get($endpoint, [
            'timeout' => 10,
            'headers' => $headers,
        ]);

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $release = self::parse_remote_release(wp_remote_retrieve_body($response), $this->version);
        if ($release && function_exists('set_transient')) {
            set_transient(self::CACHE_KEY, $release, self::CACHE_TTL);
        }

        return $release;
    }

    public function check_for_update($transient) {
        if (!is_object($transient) || empty($transient->checked)) {
            return $transient;
        }

        $release = $this->get_remote_release(false);
        if ($release && !empty($release['is_newer'])) {
            $obj = (object) [
                'slug'         => $this->slug,
                'plugin'       => $this->plugin_basename,
                'new_version'  => $release['version'],
                'url'          => $release['homepage'],
                'package'      => $release['download_url'],
                'tested'       => $release['tested'],
                'requires_php' => $release['requires_php'],
            ];
            $transient->response[$this->plugin_basename] = $obj;
        } else {
            if (!isset($transient->no_update) || !is_array($transient->no_update)) {
                $transient->no_update = [];
            }
            $transient->no_update[$this->plugin_basename] = (object) [
                'slug'        => $this->slug,
                'plugin'      => $this->plugin_basename,
                'new_version' => $this->version,
                'url'         => 'https://dantaramandiri.co.id/',
            ];
        }

        return $transient;
    }

    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== $this->slug) {
            return $result;
        }

        $release = $this->get_remote_release(false);
        if (!$release) {
            return $result;
        }

        return (object) [
            'name'          => 'AnswerGraph - Modern AEO, GEO & SEO Engine',
            'slug'          => $this->slug,
            'version'       => $release['version'],
            'author'        => '<a href="https://dantaramandiri.co.id/">PT Dantara Ryaber Mandiri</a>',
            'homepage'      => $release['homepage'],
            'download_link' => $release['download_url'],
            'last_updated'  => $release['last_updated'],
            'tested'        => $release['tested'],
            'requires_php'  => $release['requires_php'],
            'sections'      => [
                'description' => 'Plugin AEO, GEO, Schema JSON-LD, LLMs.txt Manager, dan Linter Regulasi Ketenagalistrikan ESDM untuk PT Dantara Ryaber Mandiri.',
                'changelog'   => nl2br(esc_html($release['changelog'])),
            ],
        ];
    }

    public function attach_auth_header($args, $url) {
        if ($this->token === '') {
            return $args;
        }
        $host = parse_url((string) $url, PHP_URL_HOST);
        if ($host && (strpos($host, 'github.com') !== false || strpos($host, 'githubusercontent.com') !== false)) {
            if (!isset($args['headers']) || !is_array($args['headers'])) {
                $args['headers'] = [];
            }
            $args['headers']['Authorization'] = 'Bearer ' . $this->token;
        }
        return $args;
    }

    public function normalize_zip_folder($source, $remote_source, $upgrader, $hook_extra = []) {
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_basename) {
            return $source;
        }
        global $wp_filesystem;
        if (!$wp_filesystem || !is_object($wp_filesystem)) {
            return $source;
        }

        $expected = trailingslashit($remote_source) . $this->slug . '/';
        if (trailingslashit($source) === $expected) {
            return $source;
        }

        // GitHub zipball wraps files inside owner-repo-hash/answergraph/ or owner-repo-hash/
        $nested_plugin = trailingslashit($source) . $this->slug . '/';
        $target_source = $wp_filesystem->is_dir($nested_plugin) && $wp_filesystem->exists($nested_plugin . 'answergraph.php')
            ? $nested_plugin
            : trailingslashit($source);

        if ($wp_filesystem->move($target_source, $expected, true)) {
            return $expected;
        }
        return $source;
    }

    public function add_action_links($links) {
        if ($this->update_url === '' || !function_exists('wp_nonce_url')) {
            return $links;
        }
        $check_url = wp_nonce_url(
            admin_url('admin-post.php?action=answergraph_check_update'),
            'answergraph_check_update_nonce'
        );
        $links[] = '<a href="' . esc_url($check_url) . '">Cek Update</a>';
        return $links;
    }

    public function handle_manual_check() {
        if (!current_user_can('update_plugins')) {
            wp_die('Tidak memiliki izin untuk mengecek update plugin.', 403);
        }
        check_admin_referer('answergraph_check_update_nonce');
        delete_transient(self::CACHE_KEY);
        delete_site_transient('update_plugins');
        $this->get_remote_release(true);
        if (function_exists('wp_update_plugins')) {
            wp_update_plugins();
        }
        wp_safe_redirect(admin_url('plugins.php?answergraph_checked=1'));
        exit;
    }

    public function purge_update_cache($upgrader, $options) {
        if (!empty($options['action']) && $options['action'] === 'update' && !empty($options['type']) && $options['type'] === 'plugin') {
            delete_transient(self::CACHE_KEY);
            if (function_exists('flush_rewrite_rules')) {
                flush_rewrite_rules();
            }
        }
    }
}
