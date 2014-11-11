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
}
