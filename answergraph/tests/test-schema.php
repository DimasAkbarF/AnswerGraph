<?php
require_once __DIR__ . '/runner.php';

if (file_exists(__DIR__ . '/../includes/class-answergraph-schema.php')) {
    require_once __DIR__ . '/../includes/class-answergraph-schema.php';
}

class TestAnswerGraphSchema extends AnswerGraphTestCase {
    public function test_renders_valid_schema_graph_with_wikidata_and_authorities() {
        if (!class_exists('AnswerGraph_Schema')) {
            throw new Exception("Class AnswerGraph_Schema not found.");
        }

        $builder = new AnswerGraph_Schema();
        $org_data = [
            'name'     => 'PT Dantara Ryaber Mandiri',
            'url'      => 'https://dantaramandiri.co.id',
            'logo'     => 'https://dantaramandiri.co.id/logo.png',
            'sameAs'   => [
                'https://www.wikidata.org/wiki/Q12345678',
                'https://id.wikipedia.org/wiki/Dantara_Mandiri',
                'https://www.linkedin.com/company/dantaramandiri'
            ]
        ];

        $page_data = [
            'title'       => 'Bimtek SMK2 Ketenagalistrikan',
            'url'         => 'https://dantaramandiri.co.id/bimtek-smk2',
            'description' => 'Program sertifikasi keselamatan resmi ESDM.',
            'datePublished' => '2026-01-15T08:00:00+07:00',
            'dateModified'  => '2026-11-23T21:00:00+07:00'
        ];

        $graph = $builder->generate_graph($org_data, $page_data);

        $this->assertEquals('https://schema.org', $graph['@context']);
        $this->assertArrayHasKey('@graph', $graph);
        $this->assertGreaterThanOrEqual(2, count($graph['@graph']));

        // Verify Organization node has sameAs
        $org_node = null;
        foreach ($graph['@graph'] as $node) {
            if ($node['@type'] === 'Organization') {
                $org_node = $node;
                break;
            }
        }
        $this->assertNotEmpty($org_node);
        $this->assertEquals('PT Dantara Ryaber Mandiri', $org_node['name']);
        $this->assertStringContainsString('wikidata.org', $org_node['sameAs'][0]);
    }
}

run_test_file(__FILE__);
