<?php

namespace Oro\Bundle\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update and reindex (automatically) fulltext-indexed table(s).
 * Use carefully on large data sets - do not run this task too often.
 *
 * @author magedan
 */
class ReindexCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('oro:search:reindex')
             ->setDescription('Rebuild search index')
             ->addOption(
                 'batch-size',
                 null,
                 InputArgument::OPTIONAL,
                 'How many records should be processed in one batch'
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $batchSize = $input->getOption('batch-size');
        $output->writeln('Starting reindex task' . PHP_EOL);

        /** @var $searchEngine \Oro\Bundle\SearchBundle\Engine\Orm */
        $searchEngine = $this->getContainer()->get('oro_search.search.engine');

        if ($batchSize) {
            $searchEngine->setBatchSize($batchSize);
        }

        $recordsCount = $searchEngine->reindex();

        $output->writeln(sprintf('Total indexed items: %u' . PHP_EOL, $recordsCount));
    }
}
