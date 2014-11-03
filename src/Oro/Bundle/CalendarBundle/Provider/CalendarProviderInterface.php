<?php

namespace Oro\Bundle\CalendarBundle\Provider;

interface CalendarProviderInterface
{
    /**
     * @param int   $userId
     * @param int   $calendarId
     * @param int[] $calendarIds
     *
     * @return array
     */
    public function getCalendarDefaultValues($userId, $calendarId, array $calendarIds);

    /**
     * @param int       $calendarId
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool      $subordinate
     *
     * @return array
     */
    public function getCalendarEvents($calendarId, $start, $end, $subordinate);
}
