<?php

namespace App\Indexer\Website;

class WebCrawler
{
    private int $crawl_delay;

    public function __construct(int $crawl_delay)
    {
        $this->crawl_delay = $crawl_delay;
    }

    /**
     *
     * @param string $url
     * @return string
     * @throws \Exception
     */
    public function crawl(string $url): string
    {
        $page_html = $this->fetchPage($url);
        $text = $page_html ? $this->parsePageHTML($page_html) : '';
        sleep($this->crawl_delay);
        return $this->filterText($text);
    }


    /**
     * @param string $url
     * @return string
     * @throws \Exception
     */
    private function fetchPage(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'BostonCollegeLibrariesBot');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            throw new \Exception("Error fetching {$url}\n");
        }

        return $result;
    }

    private function filterText(string $text): string
    {
        return preg_replace('/\n|\t| +/', ' ', $text);
    }

    private function parsePageHTML(string $page_html): string
    {
        $text = '';
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($page_html);
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//div[@class="s-lib-box-container"]') as $node) {
            $text .= $node->textContent;
        }
        return $text;
    }
}
