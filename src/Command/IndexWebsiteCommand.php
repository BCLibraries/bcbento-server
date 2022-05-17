<?php

namespace App\Command;

use App\Indexer\Website\Index;
use App\Indexer\Website\Indexer;
use App\Indexer\Website\LibGuidesClient;
use App\Indexer\Website\WebCrawler;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class IndexWebsiteCommand extends Command
{
    protected static $defaultName = 'website:index';
    private const SUCCESS = 0;
    private const FAILURE = 1;
    private Index $index;
    private LibGuidesClient $libguides;
    private WebCrawler $crawler;

    public function __construct(Index $index, LibGuidesClient $libguides, WebCrawler $crawler)
    {
        parent::__construct();
        $this->index = $index;
        $this->libguides = $libguides;
        $this->crawler = $crawler;
    }

    /**
     * Add more options and whatnot
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Index the website, including LibGuides')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Reindex all guides instead of only updated guides')
            ->addOption('index-name', null, InputOption::VALUE_REQUIRED, 'Name for the index, if not website')
            ->setHelp('This command crawls and reindexes the Libraries web site (i.e. LibGuides). By default it only indexes pages updated since the last indexing run.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $styled_out = new SymfonyStyle($input, $output);

        try {
            if ($input->getOption('index-name')) {
                $index_name = $input->getOption('index-name');
                $this->index->setIndexName($index_name);
                $styled_out->writeln("Indexing to {$index_name}");
            }

            $indexer = new Indexer($this->index, $this->libguides, $this->crawler, $output);

            if ($input->getOption('all')) {
                $indexer->indexAllGuides();
            } else {
                $indexer->indexUpdatedGuides();
            }

            $styled_out->success("Indexing completed successfully");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->displayException($styled_out, $e);
            return self::FAILURE;
        }

    }

    /**
     * Display an exception
     *
     * @param SymfonyStyle $styled_out
     * @param \Exception $e
     * @return void
     */
    private function displayException(SymfonyStyle $styled_out, \Exception $e): void
    {
        $styled_out->error($e->getMessage());
        $styled_out->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $styled_out->writeln($e->getTraceAsString());
    }

}