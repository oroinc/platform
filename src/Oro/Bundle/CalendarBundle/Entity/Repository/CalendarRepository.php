<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CalendarBundle\Entity\Calendar;

class CalendarRepository extends EntityRepository
{
    /**
     * Gets user's calendar
     *
     * @param int $userId
     *
     * @return Calendar
     */
    public function findByUser($userId)
    {
        return $this->findOneBy(array('owner' => $userId));
    }

    /**
     * Gets user's calendar in scope of organization
     *
     * @param $userId
     * @param $organizationId
     *
     * @return null|object
     */
    public function findByUserAndOrganization($userId, $organizationId)
    {
        return $this->findOneBy(
            array(
                'owner'        => $userId,
                'organization' => $organizationId
            )
        );
    }
}
