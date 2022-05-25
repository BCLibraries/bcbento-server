<?php

namespace App\Indexer\Website;

use Symfony\Component\Console\Output\OutputInterface;

class Indexer
{
    private Index $index;
    private LibGuidesClient $libguides;
    private WebCrawler $crawler;
    private OutputInterface $out;

    public function __construct(Index $index, LibGuidesClient $libguides, WebCrawler $crawler, OutputInterface $out)
    {
        $this->index = $index;
        $this->libguides = $libguides;
        $this->crawler = $crawler;
        $this->out = $out;
    }

    /**
     * Index guides modified since the last addition to the index
     *
     * @return void
     * @throws \Exception
     */
    public function indexUpdatedGuides()
    {
        $this->out->writeln("Fetching last updated date…");
        $last_updated = $this->index->getLastUpdated();
        $this->out->writeln("  Fetching all guides updated since {$last_updated->format(DATE_ATOM)}");
        $guides = $this->libguides->fetchGuides($last_updated);
        $this->indexGuides($guides);
    }

    /**
     * Index all guides
     *
     * @return void
     * @throws \Exception
     */
    public function indexAllGuides()
    {
        $this->out->writeln("Fetching all guides…");
        $guides = $this->libguides->fetchGuides();
        $this->indexGuides($guides);
    }

    /**
     * Index some guides
     *
     * @param Guide[] $guides
     * @return void
     * @throws \Exception
     */
    private function indexGuides(array $guides)
    {
        $num_guides = count($guides);
        $this->out->writeln("  found {$num_guides} to index");

        foreach($guides as $guide) {

            $this->out->writeln("Indexing {$guide->title}");
            foreach ($guide->pages as $page) {
                $this->out->writeln("  Crawling {$page->getTitle()}");
                $text = $this->crawler->crawl($page->getUrl());
                $page->setText($text);

                $this->out->writeln("  Indexing {$page->getTitle()}");
                $this->index->update($page);
            }
        }
    }
}