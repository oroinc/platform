<?php
declare(strict_types=1);

namespace Oro\Bundle\SearchBundle\Command;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Rebuilds the search index.
 */
class ReindexCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:search:reindex';

    private DoctrineHelper $doctrineHelper;
    private IndexerInterface $asyncIndexer;
    private IndexerInterface $syncIndexer;

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

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('class', InputArgument::OPTIONAL, 'Entity to reindex (FQCN or short name)')
            ->addOption('scheduled', null, InputOption::VALUE_NONE, 'Schedule the reindexation in the background')
            ->setDescription('Rebuilds the search index.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command rebuilds the search index.

  <info>php %command.full_name%</info>

You can limit the reindexation to a specific entity with the <info>--class</info> option.
Both the FQCN (Oro\Bundle\UserBundle\Entity\User) and short (OroUserBundle:User)
class names are accepted:

  <info>php %command.full_name% <entityClass></info>

The <info>--scheduled</info> options allows to run the reindexation in the background.
It will only schedule the job by adding a message to the message queue, so ensure
that the message consumer processes (<info>oro:message-queue:consume</info>) are running
for the reindexation to happen.

  <info>php %command.full_name% --scheduled</info>

HELP
            )
            ->addUsage('--class=<entity>')
            ->addUsage('--scheduled')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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

        return 0;
    }

    protected function getSearchIndexer($useAsynchronousIndexer = false): IndexerInterface
    {
        return $useAsynchronousIndexer === true ? $this->asyncIndexer : $this->syncIndexer;
    }
}
