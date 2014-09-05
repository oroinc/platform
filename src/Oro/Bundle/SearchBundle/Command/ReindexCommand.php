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
            ->addArgument(
                'class',
                InputArgument::OPTIONAL,
                'Full or compact class name of entity which should be reindexed' .
                '(f.e. Oro\Bundle\UserBundle\Entity\User or OroUserBundle:User)'
            )
             ->setDescription('Rebuild search index');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class       = $input->getArgument('class');
        $placeholder = $class ? '"' . $class .'" entity' : 'all mapped entities';

        $output->writeln('Starting reindex task for ' . $placeholder);

        /** @var $searchEngine \Oro\Bundle\SearchBundle\Engine\AbstractEngine */
        $searchEngine = $this->getContainer()->get('oro_search.search.engine');
        $recordsCount = $searchEngine->reindex($class);

        $output->writeln(sprintf('Total indexed items: %u', $recordsCount));
    }
}
