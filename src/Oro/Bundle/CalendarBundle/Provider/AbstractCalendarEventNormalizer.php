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
    public function __construct(ReminderManager $reminderManager)
    {
        $this->reminderManager = $reminderManager;
    }

    /**
     * Converts calendar events returned by the given query to form that can be used in API
     *
     * @param int           $calendarId The target calendar id
     * @param AbstractQuery $query      The query that should be used to get events
     *
     * @return array
     */
    public function getCalendarEvents($calendarId, AbstractQuery $query)
    {
        $result = [];

        $rawData = $query->getArrayResult();
        foreach ($rawData as $rawDataItem) {
            $result[] = $this->transformEntity($rawDataItem);
        }
        $this->applyAdditionalData($result, $calendarId);
        foreach ($result as &$resultItem) {
            $this->applyPermissions($resultItem, $calendarId);
        }

        $this->reminderManager->applyReminders($result, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        return $result;
    }

    /**
     * Converts values of entity fields to form that can be used in API
     *
     * @param array $entity
     *
     * @return array
     */
    protected function transformEntity($entity)
    {
        $result = [];
        foreach ($entity as $field => $value) {
            $this->transformEntityField($value);
            $result[$field] = $value;
        }

        return $result;
    }

    /**
     * Prepares entity field for serialization
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
     * Applies additional properties to the given calendar events
     * The list of additional properties depends on a calendar event type
     *
     * @param array $items
     * @param int   $calendarId
     */
    protected function applyAdditionalData(&$items, $calendarId)
    {
    }

    /**
     * Applies permission to the given calendar event
     * {@see Oro\Bundle\CalendarBundle\Provider\CalendarProviderInterface::getCalendarEvents}
     *
     * @param array $item
     * @param int   $calendarId
     */
    abstract protected function applyPermissions(&$item, $calendarId);
}
