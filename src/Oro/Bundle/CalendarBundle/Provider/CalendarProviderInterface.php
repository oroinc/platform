<?php

namespace Oro\Bundle\CalendarBundle\Provider;

interface CalendarProviderInterface
{
    /**
     * Gets default properties for the given calendar
     * To remove already connected calendar just return NULL as a value for this calendar
     *
     * @param int   $organizationId The id of an organization for which this information is requested
     * @param int   $userId         The id of an user requested this information
     * @param int   $calendarId     The target calendar id
     * @param int[] $calendarIds    The list of ids of connected calendars
     *
     * @return array Each item of this array can contains any properties of a calendar you need to set as default.
     *               You can return any property defined in CalendarProperty class.
     *               If you need extra properties you can return them in 'options' array.
     *               There are several additional properties you can return as well:
     *                  calendarName - a name of a calendar. This property is mandatory.
     *                  removable - indicated whether a calendar can be disconnected from the target calendar
     *                              defaults to true
     *                  canAddEvent - indicated whether events can be added to a calendar
     *                              defaults to false
     *                  canEditEvent - indicated whether calendar's events can be edited
     *                              defaults to false
     *                  canDeleteEvent - indicated whether calendar's events can be deleted
     *                              defaults to false
     *               Also there is special property names 'options' where you can return some additional options.
     *               For example:
     *                  widgetRoute   - route name of a widget can be used to view an event. defaults to empty
     *                  widgetOptions - options of a widget can be used to view an event. defaults to empty
     */
    public function getCalendarDefaultValues($organizationId, $userId, $calendarId, array $calendarIds);

    /**
     * Gets the list of calendar events
     *
     * @param int       $organizationId The id of an organization for which this information is requested
     * @param int       $userId         The id of an user requested this information
     * @param int       $calendarId     The target calendar id
     * @param \DateTime $start          A date/time specifies the begin of a time interval
     * @param \DateTime $end            A date/time specifies the end of a time interval
     * @param array     $connections    The list of connected calendars
     *                                  key = connected calendar id
     *                                  value = visibility flag (true/false)
     * @param array     $extraField     Additional fields to select
     *
     * @return array Each item of this array should contains all properties of a calendar event.
     *               There are several additional properties you can return as well:
     *                  editable  - indicated whether an event can be modified. defaults to true
     *                  removable - indicated whether an event can be deleted. defaults to true
     *                  notifiable - indicated whether there are child events to be notified. defaults to false
     *                  reminders - the list of attached reminders. defaults to empty
     */
    public function getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections, $extraField);
}
