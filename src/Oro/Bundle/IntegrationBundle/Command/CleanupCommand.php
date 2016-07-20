<?php

namespace Oro\Bundle\IntegrationBundle\Command;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\DeletionQueryResultIterator;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;

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
                'Time interval to keep the integration statuses records. Example "2 weeks"'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $interval = $input->getOption('interval');

        $completedInterval = new \DateTime('now', new \DateTimeZone('UTC'));
        $intervalString = $interval ?: self::DEFAULT_COMPLETED_STATUSES_INTERVAL;
        $completedInterval->sub(\DateInterval::createFromDateString($intervalString));

        $failedInterval = new \DateTime('now', new \DateTimeZone('UTC'));
        $failedInterval->sub(\DateInterval::createFromDateString(self::FAILED_STATUSES_INTERVAL));

        $integrationStatuses = $this->getOldIntegrationStatusesQueryBuilder($completedInterval, $failedInterval);
        $iterator = new DeletionQueryResultIterator($integrationStatuses);
        $iterator->setBufferSize(self::BATCH_SIZE);
        $iterator->setHydrationMode(AbstractQuery::HYDRATE_SCALAR);

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
     * @param DeletionQueryResultIterator $iterator
     *
     * @param string                      $className Entity FQCN
     *
     * @throws \Exception
     */
    protected function deleteRecords(DeletionQueryResultIterator $iterator, $className)
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

        return $queryBuilder->resetDQLPart('select')
            ->select('status.id')
            ->where(
                $expr->orX(
                    $expr->andX(
                        $expr->eq('status.code', Status::STATUS_COMPLETED),
                        $expr->lt('status.date', "'{$completedInterval->format('Y-m-d H:i:s')}'")
                    ),
                    $expr->andX(
                        $expr->eq('status.code', Status::STATUS_FAILED),
                        $expr->lt('status.date', "'{$failedInterval->format('Y-m-d H:i:s')}'")
                    )
                )
            );
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
