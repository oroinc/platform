<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Types\Type;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\IntegrationBundle\Exception\LogicException;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
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
     * @param Integration $integration
     * @param string      $connectorType
     * @param array       $params
     * @param bool        $useFlush
     *
     * @throws LogicException
     */
    public function schedule(Integration $integration, $connectorType, $params = [], $useFlush = true)
    {
        if (!$integration->getEnabled()) {
            return;
        }

        $connector = $this->typesRegistry->getConnectorType($integration->getType(), $connectorType);
        if (!$connector instanceof TwoWaySyncConnectorInterface) {
            throw new LogicException(sprintf('Unable to schedule job for "%s" connector type', $connectorType));
        }

        $args = [
            '--integration=' . $integration->getId(),
            '--connector=' . $connectorType,
            '--params=' . serialize($params)
        ];

        if ($this->hasNoSameJobs($args)) {
            $this->addJob($args, $useFlush);
        }
    }

    /**
     * @param array $args
     *
     * @return bool
     */
    protected function hasNoSameJobs(array $args)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('j');
        $qb->from('JMSJobQueueBundle:Job', 'j');
        $qb->andWhere('j.command = :command');
        $qb->andWhere('j.args = :args');
        $qb->andWhere($qb->expr()->in('j.state', [Job::STATE_PENDING, Job::STATE_NEW]));
        $qb->setParameter('command', self::JOB_NAME);
        $qb->setParameter('args', $args, Type::JSON_ARRAY);
        $qb->setMaxResults(1);
        $result = $qb->getQuery()->getArrayResult();

        return empty($result);
    }

    /**
     * @param array $args
     * @param bool  $useFlush
     */
    protected function addJob(array $args, $useFlush)
    {
        $job = new Job(self::JOB_NAME, $args);

        if (true === $useFlush) {
            $this->em->persist($job);
            $this->em->flush();

            return;
        }

        $uow = $this->em->getUnitOfWork();
        $uow->persist($job);
        $jobMeta = $this->em->getMetadataFactory()->getMetadataFor('JMS\JobQueueBundle\Entity\Job');
        $uow->computeChangeSet($jobMeta, $job);

        return;
    }
}
