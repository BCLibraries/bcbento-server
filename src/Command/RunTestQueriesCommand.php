<?php

namespace App\Command;

use App\Testing\GraphQLTester;
use App\Testing\QueryBuilder;
use GraphQL\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RunTestQueriesCommand extends Command
{
    protected static $defaultName = 'test:test';
    protected const DEFAULT_SERVER = 'http://localhost:8000/graphql';
    public const SUCCESS = 0;
    public const FAILURE = 1;

    /**
     * Add more options and whatnot
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Run a set of test queries against the GraphQL server')
            ->addArgument('url', InputArgument::OPTIONAL, 'The GraphQL server URL', self::DEFAULT_SERVER);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $styled_out = new SymfonyStyle($input, $output);
        $url = $input->getArgument('url');
        if (!$this->urlIsValid($url)) {
            $styled_out->error("$url is not  valid GraphQL server URL");
        }
        $client = new Client($input->getArgument('url'));
        $result = $client->runRawQuery(QueryBuilder::buildCatalogQuery('jstor'));
        var_dump($result->getData());
        return self::SUCCESS;
    }

    /**
     * Return true if the URL could be a valid GraphQL server
     *
     * We don't actually check to see if the server really is a server here, just
     * that the
     *
     * @param string $url
     * @return bool
     */
    private function urlIsValid(string $url): bool
    {
        // Make sure it's something link a URL.
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Make sure it's an http(s) URL.
        if (!preg_match('/^https?:\/\//', $url)) {
            return false;
        }
        return true;
    }
}