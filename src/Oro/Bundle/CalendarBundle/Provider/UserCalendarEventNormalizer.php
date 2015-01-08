<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;

class UserCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ReminderManager $reminderManager
     * @param SecurityFacade  $securityFacade
     * @param DoctrineHelper  $doctrineHelper
     */
    public function __construct(
        ReminderManager $reminderManager,
        SecurityFacade $securityFacade,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($reminderManager);
        $this->securityFacade = $securityFacade;
        $this->doctrineHelper = $doctrineHelper;
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
        $item = $this->transformEntity($this->serializeCalendarEvent($event));
        if (!$calendarId) {
            $calendarId = $item['calendar'];
        }

        $result = [$item];
        $this->applyAdditionalData($result, $calendarId);
        $this->applyPermissions($result[0], $calendarId);
        $this->reminderManager->applyReminders($result, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        return $result[0];
    }

    /**
     * @param CalendarEvent $event
     *
     * @return array
     */
    protected function serializeCalendarEvent(CalendarEvent $event)
    {
        return [
            'id'               => $event->getId(),
            'title'            => $event->getTitle(),
            'description'      => $event->getDescription(),
            'start'            => $event->getStart(),
            'end'              => $event->getEnd(),
            'allDay'           => $event->getAllDay(),
            'backgroundColor'  => $event->getBackgroundColor(),
            'createdAt'        => $event->getCreatedAt(),
            'updatedAt'        => $event->getUpdatedAt(),
            'invitationStatus' => $event->getInvitationStatus(),
            'parentEventId'    => $event->getParent() ? $event->getParent()->getId() : null,
            'calendar'         => $event->getCalendar() ? $event->getCalendar()->getId() : null
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function applyAdditionalData(&$items, $calendarId)
    {
        $parentEventIds = $this->getParentEventIds($items);
        if (!empty($parentEventIds)) {
            /** @var CalendarEventRepository $repo */
            $repo     = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
            $invitees = $repo->getInvitedUsersByParentsQueryBuilder($parentEventIds)
                ->getQuery()
                ->getArrayResult();

            $groupedInvitees = [];
            foreach ($invitees as $invitee) {
                $groupedInvitees[$invitee['parentEventId']][] = $invitee;
            }

            foreach ($items as &$item) {
                $item['invitedUsers'] = [];
                if (isset($groupedInvitees[$item['id']])) {
                    foreach ($groupedInvitees[$item['id']] as $invitee) {
                        $item['invitedUsers'][] = $invitee['userId'];
                    }
                }
            }
        } else {
            foreach ($items as &$item) {
                $item['invitedUsers'] = [];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function applyPermissions(&$item, $calendarId)
    {
        $item['editable']     =
            ($item['calendar'] === $calendarId)
            && empty($item['parentEventId'])
            && $this->securityFacade->isGranted('oro_calendar_event_update');
        $item['removable']    =
            ($item['calendar'] === $calendarId)
            && $this->securityFacade->isGranted('oro_calendar_event_delete');
        $item['notifiable'] =
            !empty($item['invitationStatus'])
            && empty($item['parentEventId'])
            && !empty($item['invitedUsers']);
    }

    /**
     * @param array  $items
     *
     * @return array
     */
    protected function getParentEventIds(array $items)
    {
        $ids = [];
        foreach ($items as $item) {
            if (empty($item['parentEventId'])) {
                $ids[] = $item['id'];
            }
        }

        return $ids;
    }
}
