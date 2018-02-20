<?php

namespace Oro\Bundle\IntegrationBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to clean up old integration status records
 */
class CleanupCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const BATCH_SIZE = 100;
    const COMMAND_NAME = 'oro:cron:integration:cleanup';
    const FAILED_STATUSES_INTERVAL = '1 month';
    const DEFAULT_COMPLETED_STATUSES_INTERVAL =  '1 week';

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '0 1 * * *';
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $completedInterval = new \DateTime('now', new \DateTimeZone('UTC'));
        $completedInterval->sub(\DateInterval::createFromDateString(self::DEFAULT_COMPLETED_STATUSES_INTERVAL));

        $failedInterval = new \DateTime('now', new \DateTimeZone('UTC'));
        $failedInterval->sub(\DateInterval::createFromDateString(self::FAILED_STATUSES_INTERVAL));

        $qb = $this->getOldIntegrationStatusesQueryBuilder($completedInterval, $failedInterval)
            ->select('COUNT(status.id)')
        ;

        $count = $qb->getQuery()->getSingleScalarResult();

        return ($count>0);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Clean up integration statuses history')
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Time interval to keep the integration statuses records. Example "2 weeks"',
                self::DEFAULT_COMPLETED_STATUSES_INTERVAL
            );
    }

    /**
     * {@inheritdoc}
     */
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

            return;
        }
        $output->writeln(sprintf('<comment>Integration statuses will be deleted:</comment> %d', count($iterator)));

        $this->deleteRecords($iterator, 'OroIntegrationBundle:Status');

        $output->writeln('<info>Integration statuses history cleanup completed</info>');
    }

    /**
     * Delete records using iterator
     *
     * @param BufferedIdentityQueryResultIterator $iterator
     *
     * @param string                      $className Entity FQCN
     *
     * @throws \Exception
     */
    protected function deleteRecords(BufferedIdentityQueryResultIterator $iterator, $className)
    {
        $iteration = 0;

        $ids = [];
        foreach ($iterator as $row) {
            $ids[] = reset($row);

            $iteration++;
            if ($iteration % self::BATCH_SIZE == 0) {
                $this->processDeletion($ids, $className);
            }
        }
        if ($iteration % self::BATCH_SIZE > 0) {
            $this->processDeletion($ids, $className);
        }
    }

    /**
     * @param array $ids
     * @param string $className
     */
    protected function processDeletion($ids, $className)
    {
        $this->getEntityManager()
            ->getRepository($className)
            ->createQueryBuilder('entity')
            ->delete($className, 'entity')
            ->where('entity.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }

    /**
     * @param \DateTime $completedInterval
     * @param \DateTime $failedInterval
     *
     * @return QueryBuilder
     */
    protected function getOldIntegrationStatusesQueryBuilder($completedInterval, $failedInterval)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroIntegrationBundle:Status')
            ->createQueryBuilder('status');

        $expr = $queryBuilder->expr();
        $excludes = $this->prepareExcludes();
        $queryBuilder = $queryBuilder->resetDQLPart('select')
            ->select('status.id')
            ->where(
                $expr->andX(
                    $expr->orX(
                        $expr->andX(
                            $expr->eq('status.code', "'" . Status::STATUS_COMPLETED . "'"),
                            $expr->lt('status.date', "'{$completedInterval->format('Y-m-d H:i:s')}'")
                        ),
                        $expr->andX(
                            $expr->eq('status.code', "'" . Status::STATUS_FAILED . "'"),
                            $expr->lt('status.date', "'{$failedInterval->format('Y-m-d H:i:s')}'")
                        )
                    ),
                    $expr->notIn('status.id', $excludes)
                )
            );

        return $queryBuilder;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Exclude last connector status by date
     *
     * @return array
     */
    protected function prepareExcludes()
    {
        /** @var Connection $connection */
        $connection = $this->getEntityManager()->getConnection();
        /** @var NativeQueryExecutorHelper $nativeQueryExecutorHelper */
        $nativeQueryExecutorHelper = $this->getContainer()->get('oro_entity.orm.native_query_executor_helper');
        $tableName = $nativeQueryExecutorHelper->getTableName(Status::class);
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
