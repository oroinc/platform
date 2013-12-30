<?php

namespace Oro\Bundle\IntegrationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ChannelRepository extends EntityRepository
{
    /** @var array */
    protected $loadedInstances = [];

    /**
     * Returns channels that have configured transports
     * Assume that they are ready for sync
     *
     * @return array
     */
    public function getConfiguredChannelsForSync()
    {
        $channels = $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.transport is NOT NULL')
            ->getQuery()
            ->getResult();

        return $channels;
    }

    /**
     * Find all channels with given type
     *
     * @param string $type
     *
     * @return array
     */
    protected function getChannelsBytType($type)
    {
        $channels = $this->createQueryBuilder('c')
            ->select('c')
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
            $this->loadedInstances[$id] = $this->createQueryBuilder('c')
                ->select('c')
                ->where('c.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getSingleResult();
        } elseif ($this->loadedInstances[$id]
            && $uow->getEntityState($this->loadedInstances[$id]) != UnitOfWork::STATE_MANAGED) {
            $this->loadedInstances[$id] = $uow->merge($this->loadedInstances[$id]);
        }

        return $this->loadedInstances[$id];
    }
}
