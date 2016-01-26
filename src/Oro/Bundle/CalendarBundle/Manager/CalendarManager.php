<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Oro\Component\PhpUtils\ArrayUtil;

use Oro\Bundle\CalendarBundle\Provider\CalendarPropertyProvider;
use Oro\Bundle\CalendarBundle\Provider\CalendarProviderInterface;

class CalendarManager
{
    /** @var CalendarPropertyProvider */
    protected $calendarPropertyProvider;

    /** @var CalendarProviderInterface[] */
    protected $providers = [];

    /**
     * @param CalendarPropertyProvider $calendarPropertyProvider
     */
    public function __construct(CalendarPropertyProvider $calendarPropertyProvider)
    {
        $this->calendarPropertyProvider = $calendarPropertyProvider;
    }

    /**
     * Registers the given provider in the chain
     *
     * @param string                    $alias
     * @param CalendarProviderInterface $provider
     */
    public function addProvider($alias, CalendarProviderInterface $provider)
    {
        $this->providers[$alias] = $provider;
    }

    /**
     * Gets calendars connected to the given calendar
     *
     * @param int $organizationId The id of an organization for which this information is requested
     * @param int $userId         The id of an user requested this information
     * @param int $calendarId     The target calendar id
     *
     * @return array
     */
    public function getCalendars($organizationId, $userId, $calendarId)
    {
        // make sure input parameters have proper types
        $userId     = (int)$userId;
        $calendarId = (int)$calendarId;

        $result = $this->calendarPropertyProvider->getItems($calendarId);

        $existing = [];
        foreach ($result as $key => $item) {
            $existing[$item['calendarAlias']][$item['calendar']] = $key;
        }

        foreach ($this->providers as $alias => $provider) {
            $calendarIds           = isset($existing[$alias]) ? array_keys($existing[$alias]) : [];
            $calendarDefaultValues = $provider->getCalendarDefaultValues(
                $organizationId,
                $userId,
                $calendarId,
                $calendarIds
            );
            foreach ($calendarDefaultValues as $id => $values) {
                if (isset($existing[$alias][$id])) {
                    $key = $existing[$alias][$id];
                    if ($values !== null) {
                        $calendar = $result[$key];
                        $this->applyCalendarDefaultValues($calendar, $values);
                        $result[$key] = $calendar;
                    } else {
                        unset($result[$key]);
                    }
                } else {
                    $values['targetCalendar'] = $calendarId;
                    $values['calendarAlias']  = $alias;
                    $values['calendar']       = $id;
                    $result[]                 = $values;
                }
            }
        }

        $this->normalizeCalendarData($result);

        return $result;
    }

    /**
     * Gets the list of calendar events
     *
     * @param int       $organizationId The id of an organization for which this information is requested
     * @param int       $userId         The id of an user requested this information
     * @param int       $calendarId     The target calendar id
     * @param \DateTime $start          A date/time specifies the begin of a time interval
     * @param \DateTime $end            A date/time specifies the end of a time interval
     * @param bool      $subordinate    Determines whether events from connected calendars should be included or not
     * @param array     $extraFields
     *
     * @return array
     */
    public function getCalendarEvents(
        $organizationId,
        $userId,
        $calendarId,
        $start,
        $end,
        $subordinate,
        $extraFields = []
    ) {
        // make sure input parameters have proper types
        $calendarId = (int)$calendarId;
        $subordinate = (bool)$subordinate;

        $allConnections = $this->calendarPropertyProvider->getItemsVisibility($calendarId, $subordinate);

        $result = [];

        foreach ($this->providers as $alias => $provider) {
            $connections = [];
            foreach ($allConnections as $c) {
                if ($c['calendarAlias'] === $alias) {
                    $connections[$c['calendar']] = $c['visible'];
                }
            }
            $events = $provider->getCalendarEvents(
                $organizationId,
                $userId,
                $calendarId,
                $start,
                $end,
                $connections,
                $extraFields
            );
            if (!empty($events)) {
                foreach ($events as &$event) {
                    $event['calendarAlias'] = $alias;
                    if (!isset($event['editable'])) {
                        $event['editable'] = true;
                    }
                    if (!isset($event['removable'])) {
                        $event['removable'] = true;
                    }
                    if (!isset($event['notifiable'])) {
                        $event['notifiable'] = false;
                    }
                }
                $result = array_merge($result, $events);
            }
        }

        return $result;
    }

    /**
     * @param array $calendars
     */
    protected function normalizeCalendarData(array &$calendars)
    {
        // apply default values and remove redundant properties
        $defaultValues = $this->getCalendarDefaultValues();
        foreach ($calendars as &$calendar) {
            $this->applyCalendarDefaultValues($calendar, $defaultValues);
        }

        ArrayUtil::sortBy($calendars, false, 'position');
    }

    /**
     * @param array $calendar
     * @param array $defaultValues
     */
    protected function applyCalendarDefaultValues(array &$calendar, array $defaultValues)
    {
        foreach ($defaultValues as $fieldName => $val) {
            // set default value for a field if the field does not exists or it's value is null
            if (!isset($calendar[$fieldName])) {
                $calendar[$fieldName] = is_callable($val)
                    ? call_user_func($val, $fieldName)
                    : $val;
            }
        }
    }

    /**
     * @return array
     */
    protected function getCalendarDefaultValues()
    {
        $result = $this->calendarPropertyProvider->getDefaultValues();

        $result['calendarName'] = null;
        $result['removable']    = true;

        return $result;
    }
}
