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
        $qb
            ->select('calendar')
            ->innerJoin('OroCalendarBundle:Calendar', 'calendar', 'WITH', 'calendar.owner = users');

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
