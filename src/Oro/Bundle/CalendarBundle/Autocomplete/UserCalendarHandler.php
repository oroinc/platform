<?php

namespace Oro\Bundle\CalendarBundle\Autocomplete;

use Oro\Bundle\UserBundle\Autocomplete\UserAclHandler;

class UserCalendarHandler extends UserAclHandler
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchQueryBuilder($search)
    {
        $qb = parent::getSearchQueryBuilder($search);
        $organization = $this->getSecurityContext()->getToken()->getOrganizationContext();

        $qb
            ->select('calendar')
            ->innerJoin('OroCalendarBundle:Calendar', 'calendar', 'WITH', 'calendar.owner = users')
            ->andWhere('calendar.organization = :organizationId')
            ->setParameter('organizationId', $organization->getId());

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($calendar)
    {
        $result = parent::convertItem($calendar->getOwner());
        $result['id'] = $calendar->getId();

        return $result;
    }
}
