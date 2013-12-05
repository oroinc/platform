<?php

namespace Oro\Bundle\IntegrationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class ChannelRepository extends EntityRepository
{
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
}
