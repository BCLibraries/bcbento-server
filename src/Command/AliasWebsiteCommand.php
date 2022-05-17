<?php

namespace App\Command;

use App\Indexer\Website\Index;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AliasWebsiteCommand extends Command
{
    protected static $defaultName = 'website:alias';
    private Index $index;

    public function __construct(Index $index)
    {
        parent::__construct();
        $this->index = $index;
    }

    /**
     * Add more options and whatnot
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Set the website alias for an index')
            ->addArgument('index', InputArgument::REQUIRED, 'The index to add the alias to');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $styled_out = new SymfonyStyle($input, $output);
        $index_to_alias = $input->getArgument('index');
        $this->index->addAlias($index_to_alias);
        $styled_out->success("Added alias 'website' to \'$index_to_alias\'");
    }
}