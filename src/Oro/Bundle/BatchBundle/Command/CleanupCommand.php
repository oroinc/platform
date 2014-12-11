<?php

namespace Oro\Bundle\BatchBundle\Command;

use Akeneo\Bundle\BatchBundle\Job\BatchStatus;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\BatchBundle\ORM\Query\DeletionQueryResultIterator;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;

/**
 * Command to clean up old batch job records
 */
class CleanupCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const FLUSH_BATCH_SIZE = 100;
    const DEFAULT_INTERVAL = '2 weeks';

    protected $jobManager;

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/30 * * * *';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:batch:cleanup')
            ->setDescription('Clean up batch history')
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Time interval to keep the batch records. Example "2 weeks"',
                self::DEFAULT_INTERVAL
            );;
    }

    protected function prepareDateInterval($intervalString = null)
    {
        $date = new \DateTime(null, new \DateTimeZone('UTC'));
        $intervalString = $intervalString ?: $this->getContainer()->getParameter('oro_batch.cleanup_interval');
        $date->sub(\DateInterval::createFromDateString($intervalString));

        return $date;
    }



    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $interval = $input->getOption('interval');

        /*$jobs = $this->getJobManager()->getRepository('AkeneoBatchBundle:JobExecution')
            ->findBy($criteria);*/

        $jobs = $this->findObsoleteBatchJobs($this->prepareDateInterval($interval));

        if (!$jobs) {
            $output->writeln('<info>There are no jobs eligible for clean up</info>');
            return;
        }

        $iteration = 0;
        $iterator = new DeletionQueryResultIterator($jobs);
        $iterator->setBufferSize(self::FLUSH_BATCH_SIZE);

        $output->writeln(
            sprintf('<comment>Batch job will be deleted:</comment> %d', count($iterator))
        );

        $em = $this->getEntityManager();
        try {
            $em->beginTransaction();

            foreach ($iterator as $batchJob) {
                $em->remove($batchJob);

                $iteration++;
                if ($iteration % self::FLUSH_BATCH_SIZE == 0) {
                    $this->finishBatch();
                }
            }
            if ($iteration % self::FLUSH_BATCH_SIZE > 0) {
                $this->finishBatch();
            }

            $em->commit();
            $output->writeln('<info>Batch job cleanup complete:</info>');
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    /**
     * Finish processed batch
     */
    protected function finishBatch()
    {
        $this->getJobManager()->flush();
        if ($this->getJobManager()->getConnection()->getTransactionNestingLevel() == 1) {
            $this->getJobManager()->clear();
        }
    }

    /**
     * @return EntityManager
     */
    protected function getJobManager()
    {
        if (!$this->jobManager) {
            $className = 'Akeneo\Bundle\BatchBundle\Entity\JobExecution';

            $this->jobManager = $this
                ->getContainer()
                ->get('doctrine')
                ->getManagerForClass($className)
                ->getRepository($className);

            //$this->jobManager = $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager();
        }

        return $this->jobManager;
    }


    public function findObsoleteBatchJobs($endTime)
    {

        return $this->getJobManager()->createQueryBuilder('je')
            ->leftJoin('je.jobInstance', 'ji')
            ->where('je.status NOT IN (:statuses)')
            ->andWhere('je.endTime > (:endTime)')
            ->setParameter(
                'statuses',
                [BatchStatus::STARTING, BatchStatus::STARTED]
            )
            ->setParameter('endTime', $endTime);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
