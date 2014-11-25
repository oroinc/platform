<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CalendarBundle\Entity\Calendar;

class CalendarRepository extends EntityRepository
{
    /**
     * Gets user's default calendar
     *
     * @param int $userId
     * @param int $organizationId
     *
     * @return Calendar|null
     */
    public function findDefaultCalendar($userId, $organizationId)
    {
        return $this->findOneBy(
            array(
                'owner'        => $userId,
                'organization' => $organizationId
            )
        );
    }

    /**
     * @param array $userIds
     * @param int   $organizationId
     *
     * @return Calendar[]
     */
    public function findDefaultCalendars(array $userIds, $organizationId)
    {
        $queryBuilder = $this->createQueryBuilder('c');

        return $queryBuilder
            ->andWhere('c.organization = :organization')->setParameter('organization', $organizationId)
            ->andWhere($queryBuilder->expr()->in('c.owner', $userIds))
            ->getQuery()
            ->getResult();
    }
}
