<?php

namespace App\Command;

use App\Indexer\Website\Index;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildWebsiteCommand extends \Symfony\Component\Console\Command\Command
{
    protected static $defaultName = 'website:build';
    private Index $index;

    public function __construct(Index $elasticsearch)
    {
        parent::__construct();
        $this->index = $elasticsearch;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $styled_out = new SymfonyStyle($input, $output);
        $new_index_name = $this->index->create();
        if (!$input->getOption('no-alias')) {
            $this->index->addAlias($new_index_name);
            $styled_out->success("Added $new_index_name with alias");
        } else {
            $styled_out->success("Added $new_index_name");
        }
    }


    /**
     * Add more options and whatnot
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Create the website index and alias')
            ->addOption('no-alias', null, InputOption::VALUE_NONE, 'Build the index without assigning an alias');
    }
}