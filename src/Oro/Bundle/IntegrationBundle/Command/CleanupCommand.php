<?php
declare(strict_types=1);

namespace Oro\Bundle\IntegrationBundle\Command;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deletes old integration status records.
 */
class CleanupCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    public const BATCH_SIZE = 100;
    public const FAILED_STATUSES_INTERVAL = '1 month';
    public const DEFAULT_COMPLETED_STATUSES_INTERVAL =  '1 week';

    /** @var string */
    protected static $defaultName = 'oro:cron:integration:cleanup';

    private ManagerRegistry $doctrine;
    private NativeQueryExecutorHelper $nativeQueryExecutorHelper;

    public function __construct(ManagerRegistry $doctrine, NativeQueryExecutorHelper $nativeQueryExecutorHelper)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->nativeQueryExecutorHelper = $nativeQueryExecutorHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '0 1 * * *';
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        $completedInterval = new \DateTime('now', new \DateTimeZone('UTC'));
        $completedInterval->sub(\DateInterval::createFromDateString(self::DEFAULT_COMPLETED_STATUSES_INTERVAL));

        $failedInterval = new \DateTime('now', new \DateTimeZone('UTC'));
        $failedInterval->sub(\DateInterval::createFromDateString(self::FAILED_STATUSES_INTERVAL));

        $qb = $this->getOldIntegrationStatusesQueryBuilder($completedInterval, $failedInterval)
            ->select('COUNT(status.id)');

        $count = $qb->getQuery()->getSingleScalarResult();

        return ($count>0);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Time interval to keep the batch records (e.g. "2 weeks")',
                self::DEFAULT_COMPLETED_STATUSES_INTERVAL
            )
            ->setDescription('Deletes old integration status records.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command deletes completed integration status records
that are older than 1 week and failed status records that are older than 1 month.

  <info>php %command.full_name%</info>

The <info>--interval</info> option can change the default completed integration status records time period
for cleanup. Any notation that can be parsed by <comment>\DateInterval::createFromDateString()</comment>
is accepted (see <comment>https://php.net/manual/dateinterval.createfromdatestring.php</comment>):

  <info>php %command.full_name% --interval=<interval></info>
  <info>php %command.full_name% --interval="2 weeks"</info>

HELP
            )
            ->addUsage('--interval=<interval>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $interval = $input->getOption('interval');

        $completedInterval = new \DateTime('now', new \DateTimeZone('UTC'));
        $completedInterval->sub(\DateInterval::createFromDateString($interval));

        $failedInterval = new \DateTime('now', new \DateTimeZone('UTC'));
        $failedInterval->sub(\DateInterval::createFromDateString(self::FAILED_STATUSES_INTERVAL));

        $integrationStatuses = $this->getOldIntegrationStatusesQueryBuilder($completedInterval, $failedInterval);
        $iterator = new BufferedIdentityQueryResultIterator($integrationStatuses);
        $iterator->setBufferSize(self::BATCH_SIZE);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);

        if (!count($iterator)) {
            $output->writeln('<info>There are no integration statuses eligible for clean up</info>');

            return 0;
        }
        $output->writeln(sprintf('<comment>Integration statuses will be deleted:</comment> %d', count($iterator)));

        $this->deleteRecords($iterator, 'OroIntegrationBundle:Status');

        $output->writeln('<info>Integration statuses history cleanup completed</info>');

        return 0;
    }

    /**
     * Delete records using iterator
     *
     * @throws \Exception
     */
    protected function deleteRecords(BufferedIdentityQueryResultIterator $iterator, string $entityFQCN): void
    {
        $iteration = 0;

        $ids = [];
        foreach ($iterator as $row) {
            $ids[] = reset($row);

            $iteration++;
            if ($iteration % self::BATCH_SIZE == 0) {
                $this->processDeletion($ids, $entityFQCN);
            }
        }
        if ($iteration % self::BATCH_SIZE > 0) {
            $this->processDeletion($ids, $entityFQCN);
        }
    }

    protected function processDeletion(array $ids, string $className): void
    {
        $this->doctrine->getRepository($className)
            ->createQueryBuilder('entity')
            ->delete($className, 'entity')
            ->where('entity.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }

    protected function getOldIntegrationStatusesQueryBuilder(
        \DateTime $completedInterval,
        \DateTime $failedInterval
    ): QueryBuilder {
        $queryBuilder = $this->doctrine->getRepository(Status::class)
            ->createQueryBuilder('status');

        $expr = $queryBuilder->expr();
        $excludes = $this->prepareExcludes();
        $queryBuilder = $queryBuilder->resetDQLPart('select')
            ->select('status.id')
            ->where(
                $expr->andX(
                    $expr->orX(
                        $expr->andX(
                            $expr->eq('status.code', ':statusCompleted'),
                            $expr->lt('status.date', ':completedInterval')
                        ),
                        $expr->andX(
                            $expr->eq('status.code', ':statusFailed'),
                            $expr->lt('status.date', ':failedInterval')
                        )
                    ),
                    $expr->notIn('status.id', ':excludes')
                )
            );
        $queryBuilder->setParameter('statusCompleted', Status::STATUS_COMPLETED, Types::STRING);
        $queryBuilder->setParameter('statusFailed', Status::STATUS_FAILED, Types::STRING);
        $queryBuilder->setParameter('completedInterval', $completedInterval, Types::DATETIME_MUTABLE);
        $queryBuilder->setParameter('failedInterval', $failedInterval, Types::DATETIME_MUTABLE);
        $queryBuilder->setParameter('excludes', $excludes);

        return $queryBuilder;
    }

    /**
     * Exclude last connector status by date
     */
    protected function prepareExcludes(): array
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Status::class);

        $connection = $em->getConnection();

        $tableName = $this->nativeQueryExecutorHelper->getTableName(Status::class);
        $selectQuery = <<<SQL
SELECT MAX(a.id) AS id
FROM 
    %s AS a
    INNER JOIN
        (
            SELECT  connector, MAX(date) AS minDate
            FROM %s AS b
            WHERE b.code = '%s'
            GROUP BY connector
        ) b ON a.connector = b.connector AND
                a.date = b.minDate
WHERE a.code = '%s'
GROUP BY 
    a.connector
SQL;
        $selectQuery = sprintf(
            $selectQuery,
            $tableName,
            $tableName,
            Status::STATUS_COMPLETED,
            Status::STATUS_COMPLETED
        );
        $data = $connection->fetchAll($selectQuery);
        $excludes = array_map(
            function ($item) {
                return $item['id'];
            },
            $data
        );

        if (empty($excludes)) {
            return [0];
        }

        return $excludes;
    }
}
