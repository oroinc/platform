<?php

namespace Oro\Bundle\NotificationBundle\Processor;

use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;

abstract class AbstractNotificationProcessor
{
    const JOB_ENTITY        = 'JMS\JobQueueBundle\Entity\Job';
    const SPOOL_ITEM_ENTITY = 'Oro\Bundle\NotificationBundle\Entity\SpoolItem';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityPool
     */
    protected $entityPool;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param EntityManager $em
     * @param EntityPool $entityPool
     */
    protected function __construct(LoggerInterface $logger, EntityManager $em, EntityPool $entityPool)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->entityPool = $entityPool;
    }

    /**
     * Add swift mailer spool send task to job queue if it has not been added earlier
     *
     * @param string $command
     * @param array  $commandArgs
     * @return Job
     */
    protected function addJob($command, $commandArgs = [])
    {
        if (!$this->hasNotFinishedJob($command)) {
            $this->entityPool->addPersistEntity($this->createJob($command, $commandArgs));
        }
    }

    /**
     * Checks if command is already queued.
     *
     * @param string $command
     * @return Job
     */
    protected function hasNotFinishedJob($command)
    {
        $count = (int)$this->em
            ->createQueryBuilder()
            ->select('COUNT(job)')
            ->from('JMSJobQueueBundle:Job', 'job')
            ->where('job.command = :command AND job.state <> :state')
            ->setParameters(array('command' => $command, 'state' => Job::STATE_FINISHED))
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Create new job queue entity.
     *
     * @param string $command
     * @param array $commandArgs
     * @return Job
     */
    protected function createJob($command, $commandArgs = array())
    {
        return new Job($command, $commandArgs);
    }
}
