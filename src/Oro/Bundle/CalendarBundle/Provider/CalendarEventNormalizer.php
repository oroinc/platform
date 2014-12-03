<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class CalendarEventNormalizer
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ReminderManager */
    protected $reminderManager;

    /**
     * @param ManagerRegistry $doctrine
     * @param SecurityFacade  $securityFacade
     * @param ReminderManager $reminderManager
     */
    public function __construct(
        ManagerRegistry $doctrine,
        SecurityFacade $securityFacade,
        ReminderManager $reminderManager
    ) {
        $this->doctrine        = $doctrine;
        $this->securityFacade  = $securityFacade;
        $this->reminderManager = $reminderManager;
    }

    /**
     * Converts calendar events returned by the given query to form that can be used in API
     *
     * @param int          $calendarId The target calendar id
     * @param QueryBuilder $qb         The query builder that should be used to get events
     *
     * @return array
     */
    public function getCalendarEvents($calendarId, QueryBuilder $qb)
    {
        $result = [];

        /** @var CalendarEvent[] $items */
        $items = $qb->getQuery()->getResult();
        foreach ($items as $item) {
            $result[] = $this->convertCalendarEvent($item, $calendarId);
        }

        $this->reminderManager->applyReminders($result, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        return $result;
    }

    /**
     * Converts calendar event to form that can be used in API
     *
     * @param CalendarEvent $event      The calendar event object
     * @param int           $calendarId The target calendar id
     *
     * @return array
     */
    public function getCalendarEvent(CalendarEvent $event, $calendarId = null)
    {
        $result = [$this->convertCalendarEvent($event, $calendarId)];
        $this->reminderManager->applyReminders($result, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        return $result[0];
    }

    /**
     * Converts calendar event to an array that can be used in API
     *
     * @param CalendarEvent $event      The calendar event object
     * @param int           $calendarId The target calendar id
     *
     * @return array
     */
    protected function convertCalendarEvent(CalendarEvent $event, $calendarId = null)
    {
        $serializedData = $this->serializeCalendarEvent($event);

        $result = [];
        foreach ($serializedData as $field => $value) {
            $this->transformEntityField($value);
            $result[$field] = $value;
        }
        if (!$calendarId) {
            $calendarId = $result['calendar'];
        }
        $result['editable']  =
            $result['calendar'] === $calendarId
            && empty($result['parentEventId'])
            && $this->securityFacade->isGranted('oro_calendar_event_update');
        $result['removable'] =
            $result['calendar'] === $calendarId
            && $this->securityFacade->isGranted('oro_calendar_event_delete');
        $result['notifiable'] =
            $event->getInvitationStatus()
            && !$event->getParent()
            && !$event->getChildEvents()->isEmpty();

        return $result;
    }

    /**
     * @param CalendarEvent $event
     *
     * @return array
     */
    protected function serializeCalendarEvent(CalendarEvent $event)
    {
        $data = [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'start' => $event->getStart(),
            'end' => $event->getEnd(),
            'allDay' => $event->getAllDay(),
            'backgroundColor' => $event->getBackgroundColor(),
            'createdAt' => $event->getCreatedAt(),
            'updatedAt' => $event->getUpdatedAt(),
            'calendar' => $event->getCalendar() ? $event->getCalendar()->getId() : null,
            'parentEventId' => $event->getParent() ? $event->getParent()->getId() : null,
            'invitationStatus' => $event->getInvitationStatus(),
            'childEvents' => [],
            'invitedUsers' => [],
        ];

        foreach ($event->getChildEvents() as $childEvent) {
            $data['childEvents'][] = $childEvent->getId();
            $data['invitedUsers'][] = $childEvent->getCalendar()->getOwner()->getId();
        }

        return $data;
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
}
