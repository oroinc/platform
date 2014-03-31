<?php

namespace Oro\Bundle\CronBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Command\Logger\OutputLogger;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

class CleanupCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const COMMAND_NAME = 'oro:cron:cleanup';
    const BATCH_SIZE   = 200;

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '7 0 * * *'; // every day
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'If option exists items won\'t be deleted, items count that match cleanup criteria will be shown'
            )
            ->setDescription('Clear cron-related log-alike tables: queue, etc');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new OutputLogger($output);
        $em     = $this->getContainer()
            ->get('doctrine.orm.entity_manager');
        $qb     = $em->createQueryBuilder();

        if ($input->getOption('dry-run')) {
            $result = $this
                ->applyCriteria($qb->select('COUNT(j.id)'))
                ->getQuery()
                ->getSingleScalarResult();

            $message = 'Will be removed %d rows';
        } else {
            $qb   = $this->applyCriteria($qb->select('j.id'));
            $jobs = $this->getResultIterator($qb);

            $em->beginTransaction();
            try {
                $result = 0;
                $jobIds = [];
                /** @var Job $job */
                foreach ($jobs as $jobId) {
                    $job = $em->getReference('JMSJobQueueBundle:Job', $jobId);

                    $incomingDepsCount = (integer)$em->createQuery(
                        "SELECT COUNT(j) FROM JMSJobQueueBundle:Job j WHERE :job MEMBER OF j.dependencies"
                    )
                        ->setParameter('job', $job)
                        ->getSingleScalarResult();

                    if ($incomingDepsCount > 0) {
                        continue;
                    }

                    $jobIds[] = $job->getId();
                    $em->remove($job);
                    $result++;

                    if (0 === $result % self::BATCH_SIZE) {
                        $this->flushBatch($em, $jobIds);
                        $jobIds = [];
                    }
                }

                if (!empty($jobIds)) {
                    $this->flushBatch($em, $jobIds);
                }

                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
                $logger->critical($e->getMessage(), ['exception' => $e]);

                return 1;
            }

            $message = 'Removed %d rows';
        }

        $logger->notice(sprintf($message, $result));
        $logger->notice('Completed');

        return 0;
    }

    /**
     * Remove job queue finished jobs older than $days
     *
     * @param QueryBuilder $qb
     * @param int          $days
     *
     * @return QueryBuilder
     */
    protected function applyCriteria(QueryBuilder $qb, $days = 1)
    {
        $date = new \DateTime(sprintf('%d days ago', $days), new \DateTimeZone('UTC'));
        $date = $date->format('Y-m-d H:i:s');

        $qb->from('JMSJobQueueBundle:Job', 'j')
            ->where('j.closedAt < ?0')
            ->andWhere('j.state = ?1')
            ->setParameters([$date, Job::STATE_FINISHED]);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return BufferedQueryResultIterator
     */
    protected function getResultIterator(QueryBuilder $qb)
    {
        return new BufferedQueryResultIterator($qb);
    }

    /**
     * Flush batch
     *
     * @param EntityManager $em
     * @param array         $ids
     */
    protected function flushBatch(EntityManager $em, array $ids)
    {
        $em->flush();
        $em->clear();

        $con = $em->getConnection();
        $con->executeUpdate(
            "DELETE FROM jms_job_statistics WHERE job_id IN (?)",
            [$ids],
            [Connection::PARAM_INT_ARRAY]
        );

        $con->executeUpdate(
            "DELETE FROM jms_job_dependencies WHERE source_job_id IN (?)",
            [$ids],
            [Connection::PARAM_INT_ARRAY]
        );
    }
}
