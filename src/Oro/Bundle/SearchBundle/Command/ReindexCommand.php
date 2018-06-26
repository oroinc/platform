<?php

namespace Oro\Bundle\SearchBundle\Command;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update and reindex (automatically) fulltext-indexed table(s).
 * Use carefully on large data sets - do not run this task too often.
 */
class ReindexCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:search:reindex';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->addArgument(
                'class',
                InputArgument::OPTIONAL,
                'Full or compact class name of entity which should be reindexed' .
                '(f.e. Oro\Bundle\UserBundle\Entity\User or OroUserBundle:User)'
            )
            ->addOption(
                'scheduled',
                null,
                InputOption::VALUE_NONE,
                'Enforces a scheduled (background) reindexation'
            )
            ->setDescription('Rebuild search index')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getArgument('class');
        $isScheduled = $input->getOption('scheduled');

        // convert short class name to FQCN
        if ($class) {
            $class = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityClass($class);
        }

        $message = $class
            ? sprintf('Started reindex task for "%s" entity', $class)
            : 'Started reindex task for all mapped entities'
        ;

        $output->writeln($message);

        $this->getSearchIndexer($isScheduled)->reindex($class);

        if (false === $isScheduled) {
            $output->writeln('Reindex finished successfully.');
        }
    }

    /**
     * @param bool $asyncIndexer False means regular, true async indexer
     *
     * @return IndexerInterface
     */
    protected function getSearchIndexer($asyncIndexer = false)
    {
        if (true === $asyncIndexer) {
            return $this->getContainer()->get('oro_search.async.indexer');
        }

        return $this->getContainer()->get('oro_search.search.engine.indexer');
    }
}
