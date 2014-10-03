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
 *
 * @author magedan
 */
class ReindexCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getArgument('class');
        if ($class) {
            // convert from short format to FQСN
            $class = $this->getContainer()->get('doctrine')
                ->getManagerForClass($class)->getClassMetadata($class)->getName();
        }

        $placeholder = $class ? '"' . $class .'" entity' : 'all mapped entities';

        $output->writeln('Starting reindex task for ' . $placeholder);

        /** @var $searchEngine EngineInterface */
        $searchEngine = $this->getContainer()->get('oro_search.search.engine');
        $recordsCount = $searchEngine->reindex($class);

        $output->writeln(sprintf('Total indexed items: %u', $recordsCount));
    }
}
