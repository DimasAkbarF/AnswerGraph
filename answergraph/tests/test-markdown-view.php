<?php
require_once __DIR__ . '/runner.php';

if (file_exists(__DIR__ . '/../includes/class-answergraph-markdown-view.php')) {
    require_once __DIR__ . '/../includes/class-answergraph-markdown-view.php';
}

class TestAnswerGraphMarkdownView extends AnswerGraphTestCase {
    public function test_converts_html_headings_and_tables_to_markdown() {
        if (!class_exists('AnswerGraph_Markdown_View')) {
            throw new Exception("Class AnswerGraph_Markdown_View not found.");
        }

        $converter = new AnswerGraph_Markdown_View();
        $html = '<nav>Menu</nav><h2>Judul Panduan</h2><p>Penjelasan <strong>tebal</strong> dengan <a href="https://example.com">tautan penting</a>.</p><table><tr><th>Item</th><th>Status</th></tr><tr><td>SKTTK</td><td>Aktif</td></tr></table><script>alert(1);</script>';
        
        $md = $converter->convert_html($html);

        $this->assertStringContainsString("## Judul Panduan", $md);
        $this->assertStringContainsString("Penjelasan **tebal** dengan [tautan penting](https://example.com).", $md);
        $this->assertStringContainsString("| Item | Status |", $md);
        $this->assertStringContainsString("| SKTTK | Aktif |", $md);
        // Ensure nav and script are stripped
        $this->assertTrue(strpos($md, 'alert(1)') === false, "Scripts should be stripped");
        $this->assertTrue(strpos($md, '<nav>') === false, "Nav HTML tags should be stripped");
    }
}

run_test_file(__FILE__);
