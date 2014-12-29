<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\QueryBuilder;
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
     * Gets default calendars for the given users
     *
     * @param int[] $userIds
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

    /**
     * Returns a query builder which can be used to get all user's calendars
     *
     * @param int $organizationId
     * @param int $userId
     *
     * @return QueryBuilder
     */
    public function getUserCalendarsQueryBuilder($organizationId, $userId)
    {
        return $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.organization = :organizationId AND c.owner = :userId')
            ->setParameter('organizationId', $organizationId)
            ->setParameter('userId', $userId);
    }
}
