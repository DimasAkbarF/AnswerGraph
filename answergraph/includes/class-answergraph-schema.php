<?php
/**
 * Semantic Entity Schema JSON-LD Generator
 * Connects website entities to the global Knowledge Graph using Schema.org @graph and sameAs.
 */

class AnswerGraph_Schema {

    /**
     * Generates interconnected JSON-LD @graph schema
     *
     * @param array $org_data Organization identity and authorities.
     * @param array $page_data Current page/post context.
     * @return array
     */
    public function generate_graph(array $org_data, array $page_data) {
        $org_id = rtrim($org_data['url'], '/') . '/#organization';
        $website_id = rtrim($org_data['url'], '/') . '/#website';
        $page_id = rtrim($page_data['url'], '/') . '/#webpage';

        $graph = [
            '@context' => 'https://schema.org',
            '@graph'   => [
                // 1. Organization Entity (Entity Disambiguation)
                [
                    '@type'       => 'Organization',
                    '@id'         => $org_id,
                    'name'        => $org_data['name'],
                    'url'         => $org_data['url'],
                    'logo'        => [
                        '@type'      => 'ImageObject',
                        'url'        => $org_data['logo'],
                        'caption'    => $org_data['name'],
                    ],
                    'sameAs'      => !empty($org_data['sameAs']) ? (array) $org_data['sameAs'] : [],
                ],
                // 2. WebSite Node
                [
                    '@type'       => 'WebSite',
                    '@id'         => $website_id,
                    'url'         => $org_data['url'],
                    'name'        => $org_data['name'],
                    'publisher'   => ['@id' => $org_id],
                    'inLanguage'  => 'id-ID',
                ],
                // 3. WebPage / Article Node
                [
                    '@type'           => 'WebPage',
                    '@id'             => $page_id,
                    'url'             => $page_data['url'],
                    'name'            => $page_data['title'],
                    'description'     => !empty($page_data['description']) ? $page_data['description'] : '',
                    'isPartOf'        => ['@id' => $website_id],
                    'about'           => ['@id' => $org_id],
                    'datePublished'   => !empty($page_data['datePublished']) ? $page_data['datePublished'] : '',
                    'dateModified'    => !empty($page_data['dateModified']) ? $page_data['dateModified'] : '',
                    'inLanguage'      => 'id-ID',
                ]
            ]
        ];

        return $graph;
    }

    /**
     * Renders JSON-LD script tag string ready for wp_head
     */
    public function render_html(array $graph) {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $json = function_exists('wp_json_encode') ? wp_json_encode($graph, $flags) : json_encode($graph, $flags);
        return "<script type=\"application/ld+json\" class=\"answergraph-schema-graph\">\n{$json}\n</script>\n";
    }
}
