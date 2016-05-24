<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;

abstract class AbstractCalendarEventNormalizer
{
    /** @var ReminderManager */
    protected $reminderManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ReminderManager $reminderManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ReminderManager $reminderManager, DoctrineHelper $doctrineHelper)
    {
        $this->reminderManager = $reminderManager;
        $this->doctrineHelper = $doctrineHelper;
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

        $calendarEventIds = array_map(
            function ($calendarEvent) {
                return $calendarEvent['id'];
            },
            $result
        );
        /** @var AttendeeRepository $attendeeRepository */
        $attendeeRepository = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Attendee');
        $attendeeLists = $attendeeRepository->getAttendeeListsByCalendarEventIds($calendarEventIds);
        foreach ($result as $key => $calendarEvent) {
            $result[$key]['attendees'] = $this->transformEntity($attendeeLists[$calendarEvent['id']]);
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
