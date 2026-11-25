<?php
require_once __DIR__ . '/runner.php';

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/../');
if (!function_exists('get_option')) {
    global $mock_bot_opts;
    $mock_bot_opts = [];
    function get_option($key, $default = '') {
        global $mock_bot_opts;
        return isset($mock_bot_opts[$key]) ? $mock_bot_opts[$key] : $default;
    }
    function update_option($key, $val, $autoload = null) {
        global $mock_bot_opts;
        $mock_bot_opts[$key] = $val;
        return true;
    }
}

if (file_exists(__DIR__ . '/../includes/class-answergraph-bot-logger.php')) {
    require_once __DIR__ . '/../includes/class-answergraph-bot-logger.php';
}

class TestAnswerGraphBotLogger extends AnswerGraphTestCase {

    public function test_detects_known_ai_bots_sanitizes_and_caps_ring_buffer() {
        if (!class_exists('AnswerGraph_Bot_Logger')) {
            throw new Exception('Class AnswerGraph_Bot_Logger not found.');
        }
        $logger = new AnswerGraph_Bot_Logger();
        $this->assertEquals('PerplexityBot', $logger->detect_bot('Mozilla/5.0 AppleWebKit (compatible; PerplexityBot/1.0)'));
        $this->assertEquals('', $logger->detect_bot('Mozilla/5.0 Chrome/120.0'));
        $this->assertEquals('', $logger->detect_bot("   \x00\x1F   "));

        for ($i = 0; $i < 110; $i++) {
            $logger->record_hit('GPTBot/1.0', '/llms.txt?i=' . $i, 200);
        }
        $summary = $logger->get_summary();
        $this->assertEquals(110, $summary['counts']['GPTBot']);
        $this->assertEquals(100, count($summary['recent']));

        // Path truncation & control char sanitization (Review Focus #1)
        $long_path = '/' . str_repeat('a', 250) . "\n\r\t";
        $logger->record_hit('ClaudeBot/1.0', $long_path, 200);
        $summary2 = $logger->get_summary();
        $latest = end($summary2['recent']);
        $this->assertTrue(strlen($latest['path']) <= 180, 'Path must be capped at 180 chars');
        $this->assertTrue(strpos($latest['path'], "\n") === false, 'No control chars in logged path');
    }

    public function test_records_capped_sanitized_activity_events_and_clears_all() {
        $logger = new AnswerGraph_Bot_Logger();
        for ($i = 0; $i < 65; $i++) {
            $logger->record_event('indexnow', "Ping URL #{$i}\n\r\x00" . str_repeat('x', 300), $i % 2 === 0 ? 'ok' : 'error');
        }
        $events = $logger->get_events();
        $this->assertEquals(50, count($events), 'Activity events must be capped at 50');

        $last = end($events);
        $this->assertEquals('indexnow', $last['source']);
        $this->assertEquals('ok', $last['level']);
        $this->assertTrue(strlen($last['message']) <= 240, 'Event message must be capped at 240 chars');
        $this->assertTrue(strpos($last['message'], "\n") === false, 'No control chars in event message');

        $logger->clear_all();
        $this->assertEquals(0, count($logger->get_events()), 'Activity events cleared');
        $summary = $logger->get_summary();
        $this->assertEquals(0, count($summary['counts']), 'Bot counts cleared');
        $this->assertEquals(0, count($summary['recent']), 'Bot recent cleared');
    }
}

run_test_file(__FILE__);
