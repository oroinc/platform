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

        $items = $query->getArrayResult();
        foreach ($items as $item) {
            $resultItem = [];
            foreach ($item as $field => $value) {
                $this->transformEntityField($value);
                $resultItem[$field] = $value;
            }
            $this->applyPermissions($resultItem, $calendarId);

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
     * Applies permission to the given calendar event
     * {@see Oro\Bundle\CalendarBundle\Provider\CalendarProviderInterface::getCalendarEvents}
     *
     * @param array $item
     * @param int   $calendarId
     */
    abstract protected function applyPermissions(&$item, $calendarId);
}
