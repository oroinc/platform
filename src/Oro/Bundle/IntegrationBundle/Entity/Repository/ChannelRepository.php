<?php

namespace Oro\Bundle\IntegrationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Status;

class ChannelRepository extends EntityRepository
{
    /** @var array */
    protected $loadedInstances = [];

    /**
     * Returns channels that have configured transports
     * Assume that they are ready for sync
     *
     * @param null|string $type
     *
     * @return array
     */
    public function getConfiguredChannelsForSync($type = null)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.transport is NOT NULL')
            ->andWhere('c.enabled = :isEnabled')
            ->setParameter('isEnabled', true);

        if (null !== $type) {
            $qb->andWhere('c.type = :type')
                ->setParameter('type', $type);
        }

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Find all integrations with given type
     *
     * @param string $type
     *
     * @deprecated since RC2 will be removed in 1.0
     * @return array
     */
    protected function getChannelsBytType($type)
    {
        $integrations = $this->createQueryBuilder('c')
            ->where('c.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();

        return $integrations;
    }

    /**
     * Load instance once and precache it in property
     *
     * @param int $id
     *
     * @return Integration
     */
    public function getOrLoadById($id)
    {
        $uow = $this->getEntityManager()->getUnitOfWork();

        if (!isset($this->loadedInstances[$id])) {
            $this->loadedInstances[$id] = $this->findOneBy(['id' => $id]);
        } else {
            $this->loadedInstances[$id] = $uow->merge($this->loadedInstances[$id]);
        }

        return $this->loadedInstances[$id];
    }

    /**
     * Adds status to integration, manual persist of newly created statuses
     *
     * @param Integration $integration
     * @param Status  $status
     */
    public function addStatus(Integration $integration, Status $status)
    {
        if ($this->getEntityManager()->isOpen()) {
            $integration = $this->getEntityManager()->merge($integration);

            $this->getEntityManager()->persist($status);
            $integration->addStatus($status);

            $this->getEntityManager()->flush();
        }
    }
}
