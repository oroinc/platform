<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;

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
     * @param CalendarProperty $connection
     *
     * @return string
     */
    public function getCalendarName(CalendarProperty $connection);

    /**
     * @param int       $userId
     * @param int       $calendarId
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool      $subordinate
     *
     * @return array
     */
    public function getCalendarEvents($userId, $calendarId, $start, $end, $subordinate);
}
