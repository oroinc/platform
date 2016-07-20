<?php

namespace Oro\Bundle\BatchBundle\Command;

use Akeneo\Bundle\BatchBundle\Job\BatchStatus;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\BatchBundle\ORM\Query\DeletionQueryResultIterator;

/**
 * Command to clean up old batch job records
 */
class CleanupCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const FLUSH_BATCH_SIZE = 100;

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
            ->setName('oro:cron:batch:cleanup')
            ->setDescription('Clean up batch history')
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Time interval to keep the batch records. Example "2 weeks"'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $interval = $input->getOption('interval');

        $batchJobs         = $this->getObsoleteBatchJobsQueryBuilder($this->prepareDateInterval($interval));
        $batchJobsIterator = new DeletionQueryResultIterator($batchJobs);
        $batchJobsIterator->setBufferSize(self::FLUSH_BATCH_SIZE);
        $batchJobsIterator->setHydrationMode(AbstractQuery::HYDRATE_SCALAR);

        if (!count($batchJobsIterator)) {
            $output->writeln('<info>There are no jobs eligible for clean up</info>');

            return;
        }
        $output->writeln(sprintf('<comment>Batch jobs will be deleted:</comment> %d', count($batchJobsIterator)));

        $this->deleteRecords($batchJobsIterator, 'AkeneoBatchBundle:JobExecution');

        $jobExecutions       = $this->getObsoleteJobInstancesQueryBuilder();
        $jobInstanceIterator = new DeletionQueryResultIterator($jobExecutions);
        $jobInstanceIterator->setBufferSize(self::FLUSH_BATCH_SIZE);
        $jobInstanceIterator->setHydrationMode(AbstractQuery::HYDRATE_SCALAR);
        $this->deleteRecords($jobInstanceIterator, 'AkeneoBatchBundle:JobInstance');

        $output->writeln('<info>Batch job history cleanup complete</info>');
    }

    /**
     * Subtract given interval from current date time
     *
     * @param string $intervalString
     *
     * @return \DateTime
     */
    protected function prepareDateInterval($intervalString = null)
    {
        $date           = new \DateTime('now', new \DateTimeZone('UTC'));
        $intervalString = $intervalString ?: $this->getContainer()->getParameter('oro_batch.cleanup_interval');
        $date->sub(\DateInterval::createFromDateString($intervalString));

        return $date;
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
            if ($iteration % self::FLUSH_BATCH_SIZE == 0) {
                $this->processBatch($ids, $className);
            }
        }
        if ($iteration % self::FLUSH_BATCH_SIZE > 0) {
            $this->processBatch($ids, $className);
        }
    }

    /**
     * @param array $ids
     * @param string $className
     */
    protected function processBatch($ids, $className)
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
     * Find job executions created before the given date time
     *
     * @param $endTime
     *
     * @return QueryBuilder
     */
    protected function getObsoleteBatchJobsQueryBuilder($endTime)
    {
        $repository = $this->getEntityManager()->getRepository('AkeneoBatchBundle:JobExecution');

        return $repository->createQueryBuilder('je')
            ->resetDQLPart('select')
            ->select('je.id')
            ->where('je.status NOT IN (:statuses)')
            ->andWhere('je.createTime < (:endTime)')
            ->setParameter(
                'statuses',
                [BatchStatus::STARTING, BatchStatus::STARTED]
            )
            ->setParameter('endTime', $endTime);
    }


    /**
     * Find job instances that don't have any job executions associated to them
     *
     * @return QueryBuilder
     */
    protected function getObsoleteJobInstancesQueryBuilder()
    {
        $repository = $this->getEntityManager()->getRepository('AkeneoBatchBundle:JobInstance');

        return $repository->createQueryBuilder('ji')
            ->resetDQLPart('select')
            ->select('ji.id')
            ->leftJoin('ji.jobExecutions', 'je')
            ->where('je.id IS NULL');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
