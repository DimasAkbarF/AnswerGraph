<?php
/**
 * AI Crawlers and Robots.txt Controller
 * Reads settings from wp_options to allow AI search bots and optionally block bulk scrapers.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AnswerGraph_Robots {

    protected $search_bots = [
        'PerplexityBot',
        'OAI-SearchBot',
        'Applebot-Extended',
        'Google-Extended',
    ];

    protected $training_bots = [
        'GPTBot',
        'ClaudeBot',
    ];

    protected $scraper_bots = [
        'CCBot',
        'Bytespider',
    ];

    /**
     * Filters robots.txt output using AnswerGraph settings.
     *
     * @param string $output Existing robots.txt rules.
     * @param bool   $public Whether the site is public.
     * @return string
     */
    public function filter_robots($output, $public = true) {
        if (!$public) {
            return $output;
        }

        $allow_search  = function_exists('get_option') ? get_option('answergraph_allow_search_bots', 'yes') : 'yes';
        $allow_train   = function_exists('get_option') ? get_option('answergraph_allow_training_bots', 'yes') : 'yes';
        $block_scrape  = function_exists('get_option') ? get_option('answergraph_block_scrapers', 'yes') : 'yes';

        $rules = "\n# --- AnswerGraph AI Crawlers Configuration ---\n";

        foreach ($this->search_bots as $bot) {
            $rules .= "User-agent: {$bot}\n";
            $rules .= ($allow_search === 'yes' ? "Allow: /\n\n" : "Disallow: /\n\n");
        }

        foreach ($this->training_bots as $bot) {
            $rules .= "User-agent: {$bot}\n";
            $rules .= ($allow_train === 'yes' ? "Allow: /\n\n" : "Disallow: /\n\n");
        }

        foreach ($this->scraper_bots as $bot) {
            $rules .= "User-agent: {$bot}\n";
            $rules .= ($block_scrape === 'yes' ? "Disallow: /\n\n" : "Allow: /\n\n");
        }

        return rtrim($output) . "\n" . $rules;
    }
}
