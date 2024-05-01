<?php

namespace App\Command;

use App\Indexer\Librarians\Librarian;
use Symfony\Component\Console\Command\Command;
use App\Indexer\Librarians\Index;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportLibrariansCommand extends Command
{
    protected static $defaultName = 'librarians:import';

    private Index $librarians_index;

    public function __construct(Index $librarians_index)
    {
        parent::__construct();
        $this->librarians_index = $librarians_index;
    }

    /**
     * Add more options and whatnot
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Import a JSON file of librarians into the index')
            ->addArgument('file', null, 'Librarians JSON file');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $styled_out = new SymfonyStyle($input, $output);

        // Read the file.
        $file = $input->getArgument('file');
        if (!file_exists($file)) {
            $styled_out->error("Could not find input file $file");
            return 1;
        }
        if (!is_readable($file)) {
            $styled_out->error("Could not read from input file $file");
            return 1;
        }

        // Scroll through the librarians and add them one at a time to Elasticsearch.
        $json = file_get_contents($file);
        $decoded = json_decode($json);
        foreach ($decoded->hits->hits as $hit) {
            $librarian = Librarian::buildFromElasticSearch($hit);
            $this->librarians_index->update($librarian);
        }

        $styled_out->success("Imported");

        return 0;
    }
}
