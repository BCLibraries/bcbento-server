<?php

namespace App\Service;

use App\Entity\Librarian;
use App\Entity\LibrarianRecommendationResponse;
use function count;
use Elasticsearch\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class LibrarianRecommender extends AbstractRecommender
{
    private const MIN_LIBRARIAN_SCORE = '.1';

    public $max_boost = 0;

    public function __construct(Client $elasticsearch, AdapterInterface $cache, $elasticsearch_version = '1.2.1')
    {
        parent::__construct($elasticsearch, $cache, $elasticsearch_version);
        $this->index = 'librarians';
    }

    public function buildQuery(string $keyword, array $taxonomy_terms): array
    {
        $must = [];
        $should = $this->buildTaxonomySubQueries($taxonomy_terms);

        $terms_query = [
            'match' => [
                'terms' => [
                    'query' => $keyword,
                    'boost' => $this->max_boost
                ]
            ],
        ];

        if (count($taxonomy_terms)) {
            $should[] = $terms_query;
        } else {
            $must[] = $terms_query;
        }

        $query = [
            'query' => [
                'bool' => []
            ]
        ];

        if (count($should)) {
            $query['query']['bool']['should'] = $should;
        }

        if (count($must)) {
            $query['query']['bool']['must'] = $must;
        }

        return $query;
    }

    public function buildResponse(array $librarians_json): LibrarianRecommendationResponse
    {
        $response = new LibrarianRecommendationResponse();

        foreach ($librarians_json['hits']['hits'] as $hit) {
            $this->addLibrarian($hit, $response);
        }

        return $response;
    }

    private function addLibrarian(array $librarian_json, LibrarianRecommendationResponse $response): void
    {
        if ($librarian_json['_score'] < self::MIN_LIBRARIAN_SCORE) {
            return;
        }

        $source = $librarian_json['_source'];

        $librarian = new Librarian(
            $librarian_json['_id'],
            $source['first_name'] . ' ' . $source['last_name'],
            $this->buildImageUrl($source),
            $source['email'],
            $librarian_json['_score'],
            $source['subjects']
        );

        $response->addLibrarian($librarian);
    }

    protected function buildTaxonomySubQueries(array $taxonomy_terms): array
    {
        // Increase to make lower-level taxonomy results comparatively more valuable.
        $level_boost_multiple = 5;

        // Increase to use more matched taxonomy terms.
        $terms_to_use = 2;

        $level_boost = 1;

        $taxonomy_queries = [];
        foreach ($taxonomy_terms as $taxonomy_term) {
            $i = 0;
            while ($i < $terms_to_use && isset($taxonomy_term[$i])) {
                $boost = $this->calculateBoost($taxonomy_term[$i], $level_boost);
                $taxonomy_queries[] = [
                    'match_phrase' => [
                        'taxonomy' => [
                            'query' => $taxonomy_term[$i]['term'],
                            'boost' => $boost
                        ]
                    ]
                ];
                $i++;
            }
            $level_boost *= $level_boost_multiple;
        }
        return $taxonomy_queries;
    }

    private function calculateBoost(array $taxonomy_term, float $level_boost): float
    {
        $boost = $taxonomy_term['total'] * $level_boost;
        $this->max_boost = $boost > $this->max_boost ? $boost : $this->max_boost;
        return $boost;
    }

    private function buildImageUrl(array $source): string
    {
        if (!$source['image']) {
            return '';
        }

        if (strpos($source['image'], 'http://') === 0) {
            return str_replace('http://', '', $source['image']);
        }

        return 'library.bc.edu/staff-portraits/' . $source['image'];
    }
}