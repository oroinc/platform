<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

/**
 * Class SyncScheduler
 *
 * @package Oro\Bundle\IntegrationBundle\Manager
 *
 * This class is responsible for job scheduling needed for two way data sync.
 */
class SyncScheduler
{
    const JOB_NAME = 'oro:integration:reverse:sync';

    /** @var EntityManager */
    protected $em;

    /** @var TypesRegistry */
    protected $typesRegistry;

    /**
     * @param EntityManager $em
     * @param TypesRegistry $typesRegistry
     */
    public function __construct(EntityManager $em, TypesRegistry $typesRegistry)
    {
        $this->em            = $em;
        $this->typesRegistry = $typesRegistry;
    }

    /**
     * Schedules backward sync job
     *
     * @param Channel $channel
     * @param string  $connectorType
     * @param array   $params
     * @param bool    $useFlush
     *
     * @throws \LogicException
     */
    public function schedule(Channel $channel, $connectorType, $params = [], $useFlush = true)
    {
        $connector = $this->typesRegistry->getConnectorType($channel->getType(), $connectorType);

        if (!$connector instanceof TwoWaySyncConnectorInterface) {
            throw new \LogicException(sprintf('Unable to schedule job for "%s" connector type', $connectorType));
        }

        $args = [
            '--channel=' . $channel->getId(),
            '--connector=' . $connectorType,
            '--params=' . serialize($params)
        ];
        $job  = new Job(self::JOB_NAME, $args);

        if ($useFlush) {
            $this->em->persist($job);
            $this->em->flush();
        } else {
            $uow = $this->em->getUnitOfWork();
            $uow->persist($job);
            $jobMeta = $this->em->getMetadataFactory()->getMetadataFor('JMS\JobQueueBundle\Entity\Job');
            $uow->computeChangeSet($jobMeta, $job);
        }
    }
}
