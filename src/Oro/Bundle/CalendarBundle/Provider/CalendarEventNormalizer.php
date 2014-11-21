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

        $items = $qb->getQuery()->getResult();
        foreach ($items as $item) {
            $item = $this->serializeCalendarEvent($item);
            $resultItem = array();
            foreach ($item as $field => $value) {
                $this->transformEntityField($value);
                $resultItem[$field] = $value;
            }
            $resultItem['editable']  =
                $resultItem['calendar'] === $calendarId
                && empty($resultItem['parentEventId'])
                && $this->securityFacade->isGranted('oro_calendar_event_update');
            $resultItem['removable'] =
                $resultItem['calendar'] === $calendarId
                && $this->securityFacade->isGranted('oro_calendar_event_delete');

            $result[] = $resultItem;
        }

        $this->reminderManager->applyReminders($result, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

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
