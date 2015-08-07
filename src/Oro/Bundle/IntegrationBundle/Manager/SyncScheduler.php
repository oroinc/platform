<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\IntegrationBundle\Exception\LogicException;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

/**
 * This class is responsible for job scheduling needed for two way data sync.
 */
class SyncScheduler
{
    const JOB_NAME = 'oro:integration:reverse:sync';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var TypesRegistry */
    protected $typesRegistry;

    /**
     * @param ManagerRegistry $registry
     * @param TypesRegistry   $typesRegistry
     */
    public function __construct(ManagerRegistry $registry, TypesRegistry $typesRegistry)
    {
        $this->registry      = $registry;
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
        if (!$integration->isEnabled()) {
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

        if (!$this->isScheduled($args)) {
            $job = new Job(self::JOB_NAME, $args);

            /** @var EntityManager $em */
            $em = $this->registry->getManagerForClass('JMSJobQueueBundle:Job');
            $em->persist($job);

            if (true === $useFlush) {
                $em->flush();
            } else {
                $jobMeta = $em->getClassMetadata('JMSJobQueueBundle:Job');
                $em->getUnitOfWork()->computeChangeSet($jobMeta, $job);
            }
        }
    }

    /**
     * Check whether job for export is already scheduled and waiting to be processed
     *
     * @param array $args
     *
     * @return bool
     */
    protected function isScheduled(array $args)
    {
        $qb = $this->registry->getRepository('JMSJobQueueBundle:Job')->createQueryBuilder('j');
        $qb->select('count(j.id)');
        $qb->andWhere('j.command = :command');
        $qb->andWhere('cast(j.args as text) = :args');
        $qb->andWhere($qb->expr()->in('j.state', [Job::STATE_PENDING, Job::STATE_NEW]));
        $qb->setParameter('command', self::JOB_NAME);
        $qb->setParameter('args', $args, Type::JSON_ARRAY);

        return $qb->getQuery()->getSingleScalarResult();
    }
}
