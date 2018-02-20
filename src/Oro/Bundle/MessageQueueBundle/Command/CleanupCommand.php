<?php

namespace Oro\Bundle\MessageQueueBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Job\Job as JobComponent;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const COMMAND_NAME = 'oro:cron:message-queue:cleanup';
    const INTERVAL_FOR_SUCCESSES = '-2 weeks';
    const INTERVAL_FOR_FAILED = '-1 month';

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
            ->setName(static::COMMAND_NAME)
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
        return $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManagerForClass(Job::class);
    }

    /**
     * @param QueryBuilder $qb
     * @return mixed
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
            ->setParameter('success_end_time', new \DateTime(self::INTERVAL_FOR_SUCCESSES, new \DateTimeZone('UTC')))
            ->setParameter('status_failed', JobComponent::STATUS_FAILED)
            ->setParameter('failed_end_time', new \DateTime(self::INTERVAL_FOR_FAILED, new \DateTimeZone('UTC')));
    }
}
