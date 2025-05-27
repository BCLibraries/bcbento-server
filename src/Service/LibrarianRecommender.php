<?php

namespace App\Service;

use App\Entity\Librarian;
use App\Entity\LibrarianRecommendationResponse;
use Elastic\Elasticsearch\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use function count;

/**
 * Recommender for librarians
 *
 * See AbstractRecommender for an explanation of how recommenders work.
 *
 * @package App\Service
 */
class LibrarianRecommender extends AbstractRecommender
{

    // Don't return any librarian recommendations that score less than this.
    private const MIN_LIBRARIAN_SCORE = '.6';

    public $max_boost = 1;

    public function __construct(Client $elasticsearch, AdapterInterface $cache, $elasticsearch_version = '1.2.1')
    {
        parent::__construct($elasticsearch, $cache, $elasticsearch_version);
        $this->index = 'librarians';
    }

    public function fetchRecommendation(string $keyword)
    {
        return $this->getResult($keyword, []);
    }

    /**
     * Build the query from common terms
     *
     * @param string $keyword
     * @param array $taxonomy_terms
     * @return array
     */
    public function buildQuery(string $keyword, array $taxonomy_terms): array
    {
        // Strip "and".
        $keyword = str_ireplace(' and ', ' ', $keyword);
        $keyword = str_ireplace(' & ', ' ', $keyword);

        // @todo restore recommendation feature when the large DB is back up
        return [
            "query" => [
                "from" => 0,
                "size" => 3,
                "query_string" => [
                    "fields" => ["*_name", "subjects", "terms"],
                    "query" => $keyword
                ]
            ]
        ];
    }

    /**
     * Create the response
     *
     * @param array $librarians_json JSON returned from Elasticsearch
     * @return LibrarianRecommendationResponse
     */
    public function buildResponse(array $librarians_json): LibrarianRecommendationResponse
    {
        $response = new LibrarianRecommendationResponse();

        foreach ($librarians_json['hits']['hits'] as $hit) {
            $this->addLibrarian($hit, $response);
        }

        return $response;
    }

    /**
     * Parse the Elasticsearch response JSON and add a Librarian to the response
     *
     * @param array $librarian_json
     * @param LibrarianRecommendationResponse $response
     */
    private function addLibrarian(array $librarian_json, LibrarianRecommendationResponse $response): void
    {
        // Skip low scorers.
        if ($librarian_json['_score'] < self::MIN_LIBRARIAN_SCORE) {
            return;
        }

        $source = $librarian_json['_source'];

        $librarian = new Librarian(
            $librarian_json['_id'],
            $source['first_name'] . ' ' . $source['last_name'],
            $source['email'],
            $this->buildImageUrl($source),
            $librarian_json['_score'],
            $source['subjects']
        );

        $response->addLibrarian($librarian);
    }

    /**
     * Build subquery for each taxonomy term to search against
     *
     * @param array $taxonomy_terms
     * @return array
     */
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

    /**
     * Boost term weight
     *
     * There are three levels of term responses, each corresponding to a level of specificity. This
     * generates greater boosts for more specific matches.
     *
     * @param array $taxonomy_term
     * @param float $level_boost
     * @return float
     */
    private function calculateBoost(array $taxonomy_term, float $level_boost): float
    {
        $boost = $taxonomy_term['total'] * $level_boost;
        $this->max_boost = $boost > $this->max_boost ? $boost : $this->max_boost;
        return $boost;
    }

    /**
     * Build URL for librarian photo
     *
     * @param array $source
     * @return string
     */
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
