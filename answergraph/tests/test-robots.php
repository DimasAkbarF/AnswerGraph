<?php
require_once __DIR__ . '/runner.php';
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/../');
if (!function_exists('get_option')) {
    global $mock_robot_opts;
    $mock_robot_opts = [];
    function get_option($key, $default = '') {
        global $mock_robot_opts;
        return isset($mock_robot_opts[$key]) ? $mock_robot_opts[$key] : $default;
    }
}
require_once __DIR__ . '/../includes/class-answergraph-robots.php';

class TestAnswerGraphRobots extends AnswerGraphTestCase {
    public function test_distinguishes_search_bots_from_scraping_bots() {
        $robots = new AnswerGraph_Robots();
        $output = "User-agent: *\nDisallow: /wp-admin/";
        $result = $robots->filter_robots($output, true);
        
        $this->assertStringContainsString("User-agent: PerplexityBot", $result);
        $this->assertStringContainsString("Allow: /", $result);
        $this->assertStringContainsString("User-agent: OAI-SearchBot", $result);
        $this->assertStringContainsString("User-agent: ClaudeBot", $result);
        $this->assertStringContainsString("User-agent: CCBot\nDisallow: /", $result);
    }
}

run_test_file(__FILE__);
