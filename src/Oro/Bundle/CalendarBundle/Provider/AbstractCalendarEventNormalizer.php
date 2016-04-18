<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\AbstractQuery;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
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
            $item = $this->transformEntity($rawDataItem);
            $this->transformRecurrenceData($item);
            $result[] = $item;
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
        } elseif(is_array($value)) {
            $value = $this->transformEntity($value);
        }
    }

    /**
     * Transforms recurrence data into separate field.
     *
     * @param $entity
     *
     * @return self
     */
    protected function transformRecurrenceData(&$entity)
    {
        $result = [];
        $key = Recurrence::STRING_KEY;
        foreach ($entity as $field => $value) {
            if (substr($field, 0, strlen($key)) === $key) {
                unset($entity[$field]);
                if ($value !== null) {
                    $result[lcfirst(substr($field, strlen($key)))] = $value;
                }
            }
        }

        if (!empty($result)) {
            $entity[$key] = $result;
        }

        return $this;
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
