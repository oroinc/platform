<?php
declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Command;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Exception\DeleteAsyncOperationException;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deletes all obsolete asynchronous operations.
 */
class CleanupAsyncOperationsCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:api:async_operations:cleanup';

    private int $operationLifetime;
    private int $cleanupProcessTimeout;

    private DoctrineHelper $doctrineHelper;
    private EntityDeleteHandlerRegistry $deleteHandlerRegistry;

    public function __construct(
        int $operationLifetime,
        int $cleanupProcessLifetime,
        DoctrineHelper $doctrineHelper,
        EntityDeleteHandlerRegistry $deleteHandlerRegistry
    ) {
        $this->operationLifetime = $operationLifetime;
        $this->cleanupProcessTimeout = $cleanupProcessLifetime;
        $this->doctrineHelper = $doctrineHelper;
        $this->deleteHandlerRegistry = $deleteHandlerRegistry;

        parent::__construct();
    }

    public function getDefaultDefinition()
    {
        return '0 1 * * *';
    }

    public function isActive()
    {
        return true;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show the number of obsolete asynchronous operations without deleting them'
            )
            ->setDescription('Deletes all obsolete asynchronous operations.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command deletes all obsolete asynchronous operations.

  <info>php %command.full_name%</info>

The <info>--dry-run</info> option can be used to see the number of obsolete asynchronous operations
without deleting them:

  <info>php %command.full_name% --dry-run</info>

HELP
            )
            ->addUsage('--dry-run')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $minDate = date_sub(
            new \DateTime('now', new \DateTimeZone('UTC')),
            new \DateInterval(sprintf('P%dD', $this->operationLifetime))
        );
        $iterator = new BufferedIdentityQueryResultIterator($this->getOutdatedAsyncOperationsQueryBuilder($minDate));

        if ($input->getOption('dry-run')) {
            $output->writeln(sprintf(
                '<info>The number of operations that would be deleted: %d</info>',
                $iterator->count()
            ));

            return 0;
        }

        $output->writeln(sprintf(
            '<comment>The number of operations that would be deleted: %d</comment>',
            $iterator->count()
        ));

        $deleteHandler = $this->deleteHandlerRegistry->getHandler(AsyncOperation::class);
        $endTime = time() + $this->cleanupProcessTimeout;
        foreach ($iterator as $operation) {
            if (time() > $endTime) {
                $output->writeln('<info>The command was terminated by time limit.</info>');

                return 0;
            }
            try {
                $deleteHandler->delete($operation);
            } catch (DeleteAsyncOperationException $e) {
                $output->writeln(sprintf(
                    '<comment>The asynchronous operation with ID %d was not deleted. Reason: %s</comment>',
                    $operation->getId(),
                    $e->getMessage()
                ));
            }
        }

        $output->writeln('<info>The deletion complete.</info>');

        return 0;
    }

    private function getOutdatedAsyncOperationsQueryBuilder(\DateTime $minDate): QueryBuilder
    {
        return $this->doctrineHelper
            ->createQueryBuilder(AsyncOperation::class, 'o')
            ->where('o.updatedAt <= :datetime')
            ->setParameter('datetime', $minDate);
    }
}
