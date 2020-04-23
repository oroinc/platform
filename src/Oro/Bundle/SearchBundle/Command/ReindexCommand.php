<?php

namespace Oro\Bundle\SearchBundle\Command;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update and reindex (automatically) fulltext-indexed table(s).
 * Use carefully on large data sets - do not run this task too often.
 */
class ReindexCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:search:reindex';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var IndexerInterface */
    private $asyncIndexer;

    /** @var IndexerInterface */
    private $syncIndexer;

    /**
     * ReindexCommand constructor.
     * @param DoctrineHelper $doctrineHelper
     * @param IndexerInterface $asyncIndex
     * @param IndexerInterface $syncIndexer
     * @param string|null $name
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        IndexerInterface $asyncIndex,
        IndexerInterface $syncIndexer,
        ?string $name = null
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->asyncIndexer = $asyncIndex;
        $this->syncIndexer = $syncIndexer;

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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
            $class = $this->doctrineHelper->getEntityClass($class);
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
        return $asyncIndexer === true ? $this->asyncIndexer : $this->syncIndexer;
    }
}
