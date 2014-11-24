<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\AbstractQuery;

use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;

abstract class AbstractCalendarEventNormalizer
{
    /** @var ReminderManager */
    protected $reminderManager;

    /**
     * @param ReminderManager $reminderManager
     */
    public function __construct(
        ReminderManager $reminderManager
    ) {
        $this->reminderManager = $reminderManager;
    }

    /**
     * Converts calendar events returned by the given query to form that can be used in API
     *
     * @param int               $calendarId The target calendar id
     * @param AbstractQuery     $query      The query that should be used to get events
     *
     * @return array
     */
    public function getCalendarEvents($calendarId, AbstractQuery $query)
    {
        $result = [];

        $items = $query->getArrayResult();
        foreach ($items as $item) {
            $resultItem = array();
            foreach ($item as $field => $value) {
                $this->transformEntityField($value);
                $resultItem[$field] = $value;
            }
            $this->applyPermission($resultItem, $calendarId);

            $result[] = $resultItem;
        }

        $this->reminderManager->applyReminders($result, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        return $result;
    }

    /**
     * Prepare entity field for serialization
     *
     * @param mixed $value
     */
    protected function transformEntityField(&$value)
    {
        if ($value instanceof Proxy && method_exists($value, '__toString')) {
            $value = (string)$value;
        } elseif ($value instanceof \DateTime) {
            $value = $value->format('c');
        }
    }

    /**
     * Method applies permission to edit or to delete event on the calendar page
     * It must be implemented into real class for different types of calendars
     *
     * @param array     &$resultItem
     * @param int       $calendarId
     */
    abstract protected function applyPermission(&$resultItem, $calendarId);
}
