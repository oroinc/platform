<?php

namespace Oro\Bundle\NotificationBundle\Processor;

use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

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
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param EntityManager   $em
     */
    protected function __construct(LoggerInterface $logger, EntityManager $em)
    {
        $this->logger = $logger;
        $this->em     = $em;
    }

    /**
     * Add command to job queue if it has not been added earlier
     *
     * @param string $command
     * @param array $commandArgs
     * @return Job
     */
    protected function addJob($command, $commandArgs = array())
    {
        $currJob = $this->em
            ->createQuery("SELECT j FROM JMSJobQueueBundle:Job j WHERE j.command = :command AND j.state <> :state")
            ->setParameter('command', $command)
            ->setParameter('state', Job::STATE_FINISHED)
            ->getOneOrNullResult();

        if (!$currJob) {
            $job = new Job($command, $commandArgs);
            $this->insertJob($job);
        }

        return $currJob ? $currJob : $job;
    }

    /**
     * @param $job
     */
    protected function insertJob(Job $job)
    {
        $this->em->getUnitOfWork()->computeChangeSet(
            $this->em->getClassMetadata(self::JOB_ENTITY),
            $job
        );

        $this->getEntityPersister(self::JOB_ENTITY)->addInsert($job);
    }

    /**
     * @param string $entityName
     *
     * @return \Doctrine\ORM\Persisters\BasicEntityPersister
     */
    protected function getEntityPersister($entityName)
    {
        return $this->em->getUnitOfWork()->getEntityPersister($entityName);
    }
}
