<?php

namespace Oro\Bundle\IntegrationBundle\Entity\Repository;

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
     * Load instance once and precache it in property
     *
     * @param int $id
     *
     * @return Channel
     */
    public function getOrLoadById($id)
    {
        if (!isset($this->loadedInstances[$id])) {
            $this->loadedInstances[$id] = $this->createQueryBuilder('c')
                ->select('c')
                ->where('c.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getSingleResult();
        }

        return $this->loadedInstances[$id];
    }
}
