<?php

namespace Oro\Bundle\NotificationBundle\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

/**
 * EmailNotificationRepository
 */
class EmailNotificationRepository extends EntityRepository
{
    /**
     * @return ArrayCollection
     */
    public function getRules()
    {
        $rules = $this->createQueryBuilder('emn')
            ->select(array('emn', 'event'))
            ->leftJoin('emn.event', 'event')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($rules);
    }
}
