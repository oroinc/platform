<?php
declare(strict_types=1);

namespace Oro\Bundle\SearchBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates search index for specified entities.
 */
class IndexCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:search:index';

    private ManagerRegistry $registry;
    private IndexerInterface $asyncIndexer;

    public function __construct(ManagerRegistry $registry, IndexerInterface $asyncIndexer)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->asyncIndexer = $asyncIndexer;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('class', InputArgument::REQUIRED, 'Entity to reindex (FQCN or short name)')
            ->addArgument(
                'identifiers',
                InputArgument::REQUIRED|InputArgument::IS_ARRAY,
                'IDs of the entities to reindex'
            )
            ->setDescription('Updates search index for specified entities.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates search index for specified entities.

  <info>php %command.full_name% <entity> <id1> [<id2> ...]</info>

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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

        return 0;
    }
}
