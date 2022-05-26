<?php

namespace App\Command;

use App\Indexer\Librarians\Index;
use Elasticsearch\Client;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CopyLibrariansCommand extends Command
{
    protected static $defaultName = 'librarians:copy';

    private const SUCCESS = 0;
    private const FAILURE = 1;
    private Client $elasticsearch;

    public function __construct(Client $elasticsearch)
    {
        parent::__construct();
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Add more options and whatnot
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Copy an old librarians index to a new one')
            ->addArgument('new_index', InputArgument::REQUIRED, 'the new index to copy to');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $new_index_name = $input->getArgument('new_index');

        $old_index = new Index($this->elasticsearch);
        $new_index = new Index($this->elasticsearch, $new_index_name);

        $styled_out = new SymfonyStyle($input, $output);

        foreach ($old_index->getAll() as $librarian) {
            $styled_out->writeln("    Indexing {$librarian->getId()}\n");
            $new_index->update($librarian);
        }

        return self::SUCCESS;
    }
}