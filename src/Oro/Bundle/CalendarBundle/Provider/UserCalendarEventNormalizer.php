<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;

class UserCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

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
        parent::__construct($reminderManager, $doctrineHelper);
        $this->securityFacade = $securityFacade;
    }

    /**
     * Converts calendar event to form that can be used in API
     *
     * @param CalendarEvent $event      The calendar event object
     * @param int           $calendarId The target calendar id
     *
     * @param array         $extraFields
     *
     * @return array
     */
    public function getCalendarEvent(CalendarEvent $event, $calendarId = null, array $extraFields = [])
    {
        $item = $this->transformEntity($this->serializeCalendarEvent($event, $extraFields));
        if (!$calendarId) {
            $calendarId = $item['calendar'];
        }

        $result = [$item];
        $this->applyPermissions($result[0], $calendarId);
        $this->reminderManager->applyReminders($result, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        return $result[0];
    }

    /**
     * @param CalendarEvent $event
     *
     * @param array         $extraFields
     *
     * @return array
     */
    protected function serializeCalendarEvent(CalendarEvent $event, array $extraFields = [])
    {
        $propertyAccessor = $this->getPropertyAccessor();
        $extraValues = [];

        foreach ($extraFields as $field) {
            $extraValues[$field] = $propertyAccessor->getValue($event, $field);
        }

        $extraValues['invitedUsers'] = [];
        foreach ($event->getAttendees() as $attendee) {
            $extraValues['invitedUsers'][] =  $this->transformEntity([
                'displayName' => $attendee->getDisplayName(),
                'email' => $attendee->getEmail(),
                'createdAt' => $attendee->getCreatedAt(),
                'updatedAt' => $attendee->getUpdatedAt(),
                'origin' => $attendee->getOrigin() ? $attendee->getOrigin()->getId() : null,
                'status' => $attendee->getStatus() ? $attendee->getStatus()->getId() : null,
                'type' => $attendee->getType() ? $attendee->getType()->getId() : null,
            ]);
        }

        return array_merge(
            [
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
                'calendar'         => $event->getCalendar() ? $event->getCalendar()->getId() : null,
            ],
            $extraValues
        );
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

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
