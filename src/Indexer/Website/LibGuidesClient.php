<?php

namespace App\Indexer\Website;

class LibGuidesClient
{
    private string $site_id;
    private string $api_key;

    public function __construct(string $libguides_site_id, string $libguides_api_key)
    {
        $this->site_id = $libguides_site_id;
        $this->api_key = $libguides_api_key;
    }

    /**
     * @param \DateTime|null $since
     * @return Guide[]
     * @throws \Exception
     */
    public function fetchGuides(\DateTime $since = null): array
    {
        $query_string = [
            'site_id' => $this->site_id,
            'key'     => $this->api_key,
            'expand'  => 'pages,tags,subjects,metadata',
            'status'  => '1'
        ];

        $url = 'https://lgapi-us.libapps.com/1.1/guides?' . http_build_query($query_string);
        $guides_json = $this->sendRequest($url);

        $guide_build_function = [$this, 'buildGuide'];

        $guides = array_map($guide_build_function, $guides_json);

        // If a last_updated date is set, only return guides updated since then.
        if ($since) {
            $guides = array_filter($guides, function (Guide $guide) use ($since) {
                return $guide->updatedSince($since);
            });
        }

        return $guides;
    }

    private function sendRequest(string $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            $this->report("HTTP error: $error_msg", 1);
            throw new \Exception("HTTP error fetching LibGuides from $url: $error_msg");
        }
        curl_close($ch);

        return json_decode($result);
    }

    private function buildGuide(\stdClass $guide_json): Guide
    {
        $guide = new Guide();

        $canvas = [];
        $metadata = $guide_json->metadata ?? [];

        foreach ($metadata as $metadatum) {
            if ($metadatum->name === 'canvas') {
                $canvas[] = $metadatum->content;
            }
        }

        $guide->id = $guide_json->id;
        $guide->title = $guide_json->name;
        $guide->url = $guide_json->friendly_url ?? $guide_json->url;
        $guide->description = $guide_json->description ?? '';
        $guide->subjects = isset($guide_json->subjects) ? $this->buildSubjects($guide_json->subjects) : [];
        $guide->tags = isset($guide_json->tags) ? $this->buildTags($guide_json->tags) : [];
        $guide->canvas = $canvas;

        $page_build_function = [$this, 'buildPage'];
        array_walk($guide_json->pages, $page_build_function, $guide);

        return $guide;
    }

    private function buildPage(\stdClass $page_json, $key, Guide $guide)
    {
        if (! $page_json->enable_display) {
            return null;
        }

        $id = $page_json->id;
        $title = $page_json->name;
        $updated = $page_json->updated;
        $url = $page_json->friendly_url ?? $page_json->url;

        $page = new Page($id, $title, $updated, $url, $guide);
        $guide->pages[] = $page;

        return $page;
    }

    private function buildSubjects(array $subjects_json): array
    {
        return array_map(
            function ($subject) {
                return $subject->name;
            },
            $subjects_json
        );
    }

    private function buildTags(array $tags_json): array
    {
        return array_map(
            function ($tag) {
                return $tag->text;
            },
            $tags_json
        );
    }
}