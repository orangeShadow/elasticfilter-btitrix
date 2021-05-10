<?php
declare(strict_types=1);


namespace OrangeShadow\ElasticSearch\Commands;


use OrangeShadow\ElasticSearch\Services\Indexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateFullIndex extends Command
{
    protected function configure()
    {
        $this->setName('elastic:full-reindex')
            ->setDescription('Полная переиндексация для ElasticSearch');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = Indexer::createIndexer();
        $service->run();
    }
}
