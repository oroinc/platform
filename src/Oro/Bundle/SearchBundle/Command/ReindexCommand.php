<?php

namespace Oro\Bundle\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\SearchBundle\Engine\EngineInterface;

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
        $this->setName(self::COMMAND_NAME)
            ->addArgument(
                'class',
                InputArgument::OPTIONAL,
                'Full or compact class name of entity which should be reindexed' .
                '(f.e. Oro\Bundle\UserBundle\Entity\User or OroUserBundle:User)'
            )
            ->addArgument(
                'offset',
                InputArgument::OPTIONAL,
                'INTEGER. Tells indexer to start indexation from given entity number.',
                null
            )
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                'INTEGER. Limit indexation of entity by given number. ',
                null
            )
            ->setDescription('Rebuild search index');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class  = $input->getArgument('class');
        $offset = null;
        $limit  = null;
        if ($class) {
            // convert from short format to FQСN
            $class = $this->getContainer()->get('doctrine')
                ->getManagerForClass($class)->getClassMetadata($class)->getName();

            $offsetArg = $input->getArgument('offset');
            $limitArg  = $input->getArgument('limit');
            if (null !== $offsetArg && null !== $limitArg) {
                $offset = (int) $offsetArg;
                $limit  = (int) $limitArg;
            }
        }

        $placeholder = $class ? '"' . $class . '" entity' : 'all mapped entities';

        $output->writeln('Starting reindex task for ' . $placeholder);

        /** @var $searchEngine EngineInterface */
        $searchEngine = $this->getContainer()->get('oro_search.search.engine');


        $recordsCount = $searchEngine->reindex($class, $offset, $limit);

        $output->writeln(sprintf('Total indexed items: %u', $recordsCount));
    }
}
