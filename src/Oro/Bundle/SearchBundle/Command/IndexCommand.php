<?php

namespace Oro\Bundle\SearchBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexCommand extends ContainerAwareCommand
{
    const NAME = 'oro:search:index';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Update search index for specified entities with the same type')
            ->addArgument(
                'class',
                InputArgument::REQUIRED,
                'Full or compact class name of indexed entities ' .
                '(f.e. Oro\Bundle\UserBundle\Entity\User or OroUserBundle:User)'
            )
            ->addArgument(
                'identifiers',
                InputArgument::REQUIRED|InputArgument::IS_ARRAY,
                'Identifiers of indexed entities (f.e. 42)'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getArgument('class');
        $identifiers = $input->getArgument('identifiers');

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManagerForClass($class);
        if (null === $em) {
            throw new \LogicException(sprintf('Entity manager was not found for class: "%s"', $class));
        }

        $entities = [];
        foreach ($identifiers as $id) {
            $entities[] = $em->getReference($class, $id);
        }

        $this->getSearchIndexer()->save($entities);

        $output->writeln('Started index update for entities.');
    }

    /**
     * @return ManagerRegistry
     */
    protected function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * @return IndexerInterface
     */
    protected function getSearchIndexer()
    {
        return $this->getContainer()->get('oro_search.async.indexer');
    }
}
