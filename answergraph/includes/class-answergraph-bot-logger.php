<?php
/**
 * AI Bot Traffic & Citation Telemetry Logger
 * Tracks visits from recognized AI crawlers in a capped 100-entry ring buffer.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AnswerGraph_Bot_Logger {

    const OPTION_KEY          = 'answergraph_bot_logs';
    const ACTIVITY_OPTION_KEY = 'answergraph_activity_logs';
    const MAX_RECENT          = 100;
    const MAX_EVENTS          = 50;

    protected $bots = [
        'OAI-SearchBot',
        'GPTBot',
        'PerplexityBot',
        'ClaudeBot',
        'Google-Extended',
        'Applebot-Extended',
    ];

    /**
     * Strips control characters and caps string length safely without ext-mbstring.
     */
    protected function sanitize_line($value, $max_len) {
        $clean = (string) preg_replace('/[\x00-\x1F\x7F]/', '', (string) $value);
        return substr($clean, 0, (int) $max_len);
    }

    /**
     * Detects a canonical AI bot name from User-Agent, or '' if unrecognized.
     */
    public function detect_bot($user_agent) {
        $ua = trim($this->sanitize_line($user_agent, 300));
        if ($ua === '') {
            return '';
        }
        foreach ($this->bots as $bot) {
            if (stripos($ua, $bot) !== false) {
                return $bot;
            }
        }
        return '';
    }

    /**
     * Records a bot hit in wp_options (autoload = false).
     */
    public function record_hit($user_agent, $path, $status = 200) {
        $bot = $this->detect_bot($user_agent);
        if ($bot === '') {
            return false;
        }

        $clean_path = $this->sanitize_line($path, 180);

        $data = $this->get_summary();
        if (!isset($data['counts'][$bot])) {
            $data['counts'][$bot] = 0;
        }
        $data['counts'][$bot]++;

        $data['recent'][] = [
            'bot'    => $bot,
            'path'   => $clean_path,
            'status' => (int) $status,
            'time'   => gmdate('Y-m-d H:i:s'),
        ];

        if (count($data['recent']) > self::MAX_RECENT) {
            $data['recent'] = array_slice($data['recent'], -self::MAX_RECENT);
        }

        if (function_exists('update_option')) {
            update_option(self::OPTION_KEY, $data, false);
        }
        return true;
    }

    /**
     * Returns ['counts' => [...], 'recent' => [...]].
     */
    public function get_summary() {
        $raw = function_exists('get_option') ? get_option(self::OPTION_KEY, []) : [];
        if (!is_array($raw)) {
            $raw = [];
        }
        return [
            'counts' => isset($raw['counts']) && is_array($raw['counts']) ? $raw['counts'] : [],
            'recent' => isset($raw['recent']) && is_array($raw['recent']) ? $raw['recent'] : [],
        ];
    }

    /**
     * Records a lightweight plugin activity event (IndexNow, LLMs sync, Updater) in wp_options (autoload = false).
     */
    public function record_event($source, $message, $level = 'info') {
        $clean_source = preg_replace('/[^a-z0-9_-]/i', '', (string) $source);
        $clean_source = substr($clean_source !== '' ? strtolower($clean_source) : 'system', 0, 24);
        $clean_level  = in_array($level, ['ok', 'error', 'info'], true) ? $level : 'info';
        $clean_msg    = trim($this->sanitize_line($message, 240));
        if ($clean_msg === '') {
            return false;
        }

        $events   = $this->get_events();
        $events[] = [
            'time'    => gmdate('Y-m-d H:i:s'),
            'source'  => $clean_source,
            'level'   => $clean_level,
            'message' => $clean_msg,
        ];
        if (count($events) > self::MAX_EVENTS) {
            $events = array_slice($events, -self::MAX_EVENTS);
        }

        if (function_exists('update_option')) {
            update_option(self::ACTIVITY_OPTION_KEY, $events, false);
        }
        return true;
    }

    /**
     * Returns recorded system activity events (chronological, up to MAX_EVENTS).
     */
    public function get_events() {
        $raw = function_exists('get_option') ? get_option(self::ACTIVITY_OPTION_KEY, []) : [];
        return is_array($raw) ? array_values($raw) : [];
    }

    /**
     * Clears both AI bot telemetry and activity logs.
     */
    public function clear_all() {
        if (function_exists('update_option')) {
            update_option(self::OPTION_KEY, [], false);
            update_option(self::ACTIVITY_OPTION_KEY, [], false);
        }
    }
}
