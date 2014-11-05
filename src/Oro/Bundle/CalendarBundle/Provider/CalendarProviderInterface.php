<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;

interface CalendarProviderInterface
{
    /**
     * Gets default properties for the given calendar
     *
     * @param int   $userId      The id of an user requested this information
     * @param int   $calendarId  The target calendar id
     * @param int[] $calendarIds The list of ids of connected calendars
     *
     * @return array Each item of this array can contains any properties of a calendar you need to set as default.
     *               There are properties you must return:
     *                  calendarName - a name of a calendar
     *               Also there are several additional properties you can return:
     *                  removable - indicated whether a calendar can be disconnected from the target calendar
     */
    public function getCalendarDefaultValues($userId, $calendarId, array $calendarIds);

    /**
     * Gets a name of a calendar is represented by the given connection
     *
     * @param CalendarProperty $connection
     *
     * @return string
     */
    public function getCalendarName(CalendarProperty $connection);

    /**
     * Gets the list of calendar events
     *
     * @param int       $calendarId
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool      $subordinate
     *
     * @return array Each item of this array should contains all properties of a calendar event.
     *               Also there are several additional properties you can return:
     *                  editable - indicated whether an event can be modified
     *                  removable - indicated whether an event can be deleted
     *                  reminders - the list of attached reminders
     */
    public function getCalendarEvents($calendarId, $start, $end, $subordinate);
}
