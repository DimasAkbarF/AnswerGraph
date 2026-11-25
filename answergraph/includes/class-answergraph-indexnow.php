<?php
/**
 * IndexNow Instant Submission for Bing & ChatGPT Search
 */

if (!defined('ABSPATH')) {
    exit;
}

class AnswerGraph_IndexNow {

    const KEY_OPTION     = 'answergraph_indexnow_key';
    const ENABLED_OPTION = 'answergraph_indexnow_enabled';
    const ENDPOINT       = 'https://api.indexnow.org/indexnow';

    public function get_or_create_key() {
        $key = function_exists('get_option') ? (string) get_option(self::KEY_OPTION, '') : '';
        if (preg_match('/^[a-f0-9]{32}$/i', $key)) {
            return $key;
        }
        $key = bin2hex(random_bytes(16));
        if (function_exists('update_option')) {
            update_option(self::KEY_OPTION, $key, false);
        }
        return $key;
    }

    public function should_ping_post($new_status, $post) {
        if ($new_status !== 'publish' || !is_object($post)) {
            return false;
        }
        $type = isset($post->post_type) ? (string) $post->post_type : '';
        if ($type !== 'post' && $type !== 'page') {
            return false;
        }
        if (!empty($post->post_password)) {
            return false;
        }
        return true;
    }

    public function build_payload($host, $key, array $urls, $key_location = '') {
        $clean_urls = array_values(array_filter(array_map('strval', $urls)));
        if ($key_location === '') {
            $key_location = function_exists('home_url')
                ? home_url('/' . $key . '.txt')
                : 'https://' . $host . '/' . $key . '.txt';
        }
        return [
            'host'        => (string) $host,
            'key'         => (string) $key,
            'keyLocation' => (string) $key_location,
            'urlList'     => $clean_urls,
        ];
    }

    public function ping_urls(array $urls, $blocking = false) {
        if (empty($urls) || !function_exists('wp_remote_post')) {
            return false;
        }
        $host = parse_url(home_url(), PHP_URL_HOST);
        if (!$host) {
            return false;
        }
        $key     = $this->get_or_create_key();
        $payload = $this->build_payload($host, $key, $urls);
        if (empty($payload['urlList'])) {
            return false;
        }

        $res = wp_remote_post(self::ENDPOINT, [
            'timeout'  => 5,
            'blocking' => (bool) $blocking,
            'headers'  => ['Content-Type' => 'application/json; charset=utf-8'],
            'body'     => wp_json_encode($payload),
        ]);
        if ($blocking && !is_wp_error($res)) {
            $code = (int) wp_remote_retrieve_response_code($res);
            return $code >= 200 && $code < 300;
        }
        return !is_wp_error($res);
    }
}
