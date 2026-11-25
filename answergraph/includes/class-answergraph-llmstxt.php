<?php
/**
 * Dynamic llms.txt and llms-full.txt Generator
 * Fully compliant with llmstxt.org v2 standard
 */

class AnswerGraph_LLMsTxt {

    protected $site_name;
    protected $site_desc;

    public function __construct($site_name = '', $site_desc = '') {
        $this->site_name = $site_name ?: (function_exists('get_bloginfo') ? get_bloginfo('name') : 'Website');
        $this->site_desc = $site_desc ?: (function_exists('get_bloginfo') ? get_bloginfo('description') : '');
    }

    /**
     * Renders llms.txt markdown output
     *
     * @param array $items Array of items with title, url, description, section.
     * @return string
     */
    public function render_summary(array $items) {
        $output = "# {$this->site_name}\n";
        if (!empty($this->site_desc)) {
            $output .= "> {$this->site_desc}\n";
        }
        $output .= "\n";

        // Group items by section
        $sections = [];
        foreach ($items as $item) {
            $section = !empty($item['section']) ? $item['section'] : 'Pages';
            $sections[$section][] = $item;
        }

        foreach ($sections as $section_title => $section_items) {
            $safe_section = str_replace(["\r", "\n"], ' ', $section_title);
            $output .= "## {$safe_section}\n";
            foreach ($section_items as $item) {
                $title = str_replace(["\r", "\n", '[', ']'], ' ', $item['title']);
                $url = function_exists('esc_url') ? esc_url($item['url']) : htmlspecialchars($item['url']);
                $desc = !empty($item['description']) ? ': ' . str_replace(["\r", "\n"], ' ', $item['description']) : '';
                $output .= "- [{$title}]({$url}){$desc}\n";
            }
            $output .= "\n";
        }

        return trim($output) . "\n";
    }

    /**
     * Renders llms-full.txt by combining full clean markdown of key documents
     */
    public function render_full(array $docs) {
        $output = "# {$this->site_name} (Full Documentation)\n";
        if (!empty($this->site_desc)) {
            $output .= "> {$this->site_desc}\n";
        }
        $output .= "\n---\n\n";

        foreach ($docs as $doc) {
            $output .= "# {$doc['title']}\n";
            $output .= "Source: {$doc['url']}\n\n";
            $output .= trim($doc['content']) . "\n\n---\n\n";
        }

        return trim($output) . "\n";
    }
}
