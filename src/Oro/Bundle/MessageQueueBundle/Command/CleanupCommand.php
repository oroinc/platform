<?php

namespace Oro\Bundle\MessageQueueBundle\Command;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Job\Job as JobComponent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears successes and failed jobs from message_queue_job table
 */
class CleanupCommand extends Command implements CronCommandInterface
{
    const INTERVAL_FOR_SUCCESSES = '-2 weeks';
    const INTERVAL_FOR_FAILED = '-1 month';

    /** @var string */
    protected static $defaultName = 'oro:cron:message-queue:cleanup';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function isActive()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition()
    {
        return '0 1 * * *';
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'If option exists jobs won\'t be deleted, job count that match cleanup criteria will be shown'
            )
            ->setDescription('Clear successes and failed jobs from message_queue_job table');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('dry-run')) {
            $output->writeln(
                sprintf(
                    '<info>Number of jobs that would be deleted: %d</info>',
                    $this->countRecords()
                )
            );

            return;
        }

        $output->writeln(sprintf(
            '<comment>Number of jobs that has been deleted:</comment> %d',
            $this->deleteRecords()
        ));

        $output->writeln('<info>Message queue job history cleanup complete</info>');
    }

    /**
     * @return mixed
     */
    private function deleteRecords()
    {
        $qb = $this->getEntityManager()
            ->getRepository(Job::class)
            ->createQueryBuilder('job');
        $qb->delete(Job::class, 'job');
        $this->addOutdatedJobsCriteria($qb);

        return $qb->getQuery()->execute();
    }

    /**
     * @return mixed
     */
    private function countRecords()
    {
        $qb = $this->getEntityManager()
            ->getRepository(Job::class)
            ->createQueryBuilder('job');
        $qb->select('COUNT(job.id)');
        $this->addOutdatedJobsCriteria($qb);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass(Job::class);
    }

    /**
     * @param QueryBuilder $qb
     */
    private function addOutdatedJobsCriteria(QueryBuilder $qb)
    {
        $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('job.status', ':status_success'),
                $qb->expr()->lt('job.stoppedAt', ':success_end_time')
            ))
            ->orWhere($qb->expr()->andX(
                $qb->expr()->eq('job.status', ':status_failed'),
                $qb->expr()->lt('job.stoppedAt', ':failed_end_time')
            ))
            ->setParameter('status_success', JobComponent::STATUS_SUCCESS)
            ->setParameter(
                'success_end_time',
                new \DateTime(self::INTERVAL_FOR_SUCCESSES, new \DateTimeZone('UTC')),
                Types::DATETIME_MUTABLE
            )
            ->setParameter('status_failed', JobComponent::STATUS_FAILED)
            ->setParameter(
                'failed_end_time',
                new \DateTime(self::INTERVAL_FOR_FAILED, new \DateTimeZone('UTC')),
                Types::DATETIME_MUTABLE
            );
    }
}
