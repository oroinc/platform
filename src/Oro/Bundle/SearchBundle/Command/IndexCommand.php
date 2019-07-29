<?php

namespace Oro\Bundle\SearchBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update search index for specified entities with the same type
 */
class IndexCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:search:index';

    /** @var ManagerRegistry */
    private $registry;

    /** @var IndexerInterface */
    private $asyncIndexer;

    /**
     * @param ManagerRegistry $registry
     * @param IndexerInterface $asyncIndexer
     */
    public function __construct(ManagerRegistry $registry, IndexerInterface $asyncIndexer)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->asyncIndexer = $asyncIndexer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Update search index for specified entities with the same type')
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
        $em = $this->registry->getManagerForClass($class);
        if (null === $em) {
            throw new \LogicException(sprintf('Entity manager was not found for class: "%s"', $class));
        }

        $entities = [];
        foreach ($identifiers as $id) {
            $entities[] = $em->getReference($class, $id);
        }

        $this->asyncIndexer->save($entities);

        $output->writeln('Started index update for entities.');
    }
}
