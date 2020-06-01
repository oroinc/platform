<?php

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

    /** @var int */
    private $operationLifetime;

    /** @var int */
    private $cleanupProcessTimeout;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EntityDeleteHandlerRegistry */
    private $deleteHandlerRegistry;

    /**
     * @param int                         $operationLifetime
     * @param int                         $cleanupProcessLifetime
     * @param DoctrineHelper              $doctrineHelper
     * @param EntityDeleteHandlerRegistry $deleteHandlerRegistry
     */
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

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition()
    {
        return '0 1 * * *';
    }

    /**
     * {@inheritDoc}
     */
    public function isActive()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setDescription('Deletes all obsolete asynchronous operations.')
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'If option exists asynchronous operations won\'t be deleted,'
                . ' the number of operations that match cleanup criteria will be shown.'
            );
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * @param \DateTime $minDate
     *
     * @return QueryBuilder
     */
    private function getOutdatedAsyncOperationsQueryBuilder(\DateTime $minDate): QueryBuilder
    {
        return $this->doctrineHelper
            ->createQueryBuilder(AsyncOperation::class, 'o')
            ->where('o.updatedAt <= :datetime')
            ->setParameter('datetime', $minDate);
    }
}
