<?php

namespace Oro\Bundle\IntegrationBundle\Entity\Repository;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

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
            ->where('c.transport is NOT NULL');

        if (null !== $type) {
            $qb->where('c.type = :type')
                ->setParameter('type', $type);
        }

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Find all channels with given type
     *
     * @param string $type
     *
     * @deprecated since RC2 will be removed in 1.0
     * @return array
     */
    protected function getChannelsBytType($type)
    {
        $channels = $this->createQueryBuilder('c')
            ->where('c.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();

        return $channels;
    }

    /**
     * Load instance once and precache it in property
     *
     * @param int $id
     *
     * @return Channel
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
}
