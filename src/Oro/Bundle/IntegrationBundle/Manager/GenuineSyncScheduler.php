<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\JobQueueBundle\Entity\Job;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Exception\LogicException;

/**
 * This class is responsible for job scheduling.
 */
class GenuineSyncScheduler
{
    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Schedules sync job
     *
     * @param Integration $integration
     * @param bool        $force
     *
     * @return Job
     */
    public function schedule(Integration $integration, $force = false)
    {
        if (false === $integration->isEnabled()) {
            throw new LogicException(sprintf('The integration is not active. Id: %s', $integration->getId()));
        }

        $jobParameters = [
            '--integration-id=' . $integration->getId(),
            '-v'
        ];
        if ($force) {
            $jobParameters[] = '--force';
        }
        $job = new Job(SyncCommand::COMMAND_NAME, $jobParameters);

        $em = $this->registry->getManagerForClass('OroIntegrationBundle:Channel');
        $em->persist($job);
        $em->flush();

        return $job;
    }
}
