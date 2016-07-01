<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\ORM\AbstractQuery;

use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\PropertyAccess\PropertyAccessor;

class UserCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param ReminderManager $reminderManager
     * @param SecurityFacade  $securityFacade
     * @param AttendeeManager $attendeeManager
     */
    public function __construct(
        ReminderManager $reminderManager,
        SecurityFacade $securityFacade,
        AttendeeManager $attendeeManager
    ) {
        parent::__construct($reminderManager, $attendeeManager);
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
        $this->applyAdditionalData($result, $calendarId);
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
        $extraValues = [];

        foreach ($extraFields as $field) {
            $extraValues[$field] = $this->getObjectValue($event, $field);
        }

        if ($recurrence = $event->getRecurrence()) {
            $extraValues[Recurrence::STRING_KEY] = [
                'id' => $recurrence->getId(),
                'recurrenceType' => $recurrence->getRecurrenceType(),
                'interval' => $recurrence->getInterval(),
                'instance' => $recurrence->getInstance(),
                'dayOfWeek' => $recurrence->getDayOfWeek(),
                'dayOfMonth' => $recurrence->getDayOfMonth(),
                'monthOfYear' => $recurrence->getMonthOfYear(),
                'startTime' => $recurrence->getStartTime(),
                'endTime' => $recurrence->getEndTime(),
                'occurrences' => $recurrence->getOccurrences(),
                'timezone' => $recurrence->getTimeZone()
            ];
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
                'invitationStatus' => $this->getEventStatus($event),
                'parentEventId'    => $event->getParent() ? $event->getParent()->getId() : null,
                'calendar'         => $event->getCalendar() ? $event->getCalendar()->getId() : null,
                'recurringEventId' => $event->getRecurringEvent() ? $event->getRecurringEvent()->getId() : null,
                'originalStart'    => $event->getOriginalStart(),
                'isCancelled'      => $event->isCancelled(),
            ],
            $this->prepareExtraValues($event, $extraValues)
        );
    }

    /**
     * @param CalendarEvent $event
     *
     * @return string
     */
    protected function getEventStatus(CalendarEvent $event)
    {
        $relatedAttendee = $event->getRelatedAttendee();

        if ($relatedAttendee) {
            $status = $relatedAttendee->getStatus();

            if ($status) {
                return $status->getId();
            }
        }

        return null;
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
            empty($item['parentEventId'])
            && !empty($item['attendees'])
            && empty($item['recurrence']);
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

    /**
     * @param mixed $object
     * @param string $propertyPath
     *
     * @return mixed|null
     */
    protected function getObjectValue($object, $propertyPath)
    {
        $propertyAccessor = $this->getPropertyAccessor();

        try {
            return $propertyAccessor->getValue($object, $propertyPath);
        } catch (InvalidPropertyPathException $e) {
            return null;
        } catch (NoSuchPropertyException $e) {
            return null;
        }
    }

    /**
     * @param CalendarEvent $event
     * @param array         $extraValues
     *
     * @return array
     */
    protected function prepareExtraValues(CalendarEvent $event, array $extraValues)
    {
        $extraValues['attendees']    = [];
        $extraValues['invitedUsers'] = [];

        foreach ($event->getAttendees() as $attendee) {
            $extraValues['attendees'][] = $this->transformEntity([
                'displayName' => $attendee->getDisplayName(),
                'email'       => $attendee->getEmail(),
                'userId'      => $this->getObjectValue($attendee, 'user.id'),
                'createdAt'   => $attendee->getCreatedAt(),
                'updatedAt'   => $attendee->getUpdatedAt(),
                'status'      => $this->getObjectValue($attendee, 'status.id'),
                'type'        => $this->getObjectValue($attendee, 'type.id'),
            ]);

            if ($attendee->getUser()) {
                $extraValues['invitedUsers'][] = $attendee->getUser()->getId();
            }
        }

        return $extraValues;
    }

    /**
     * {@inheritdoc}
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
        $this->addAttendeesToCalendarEvents($result);
        foreach ($result as &$resultItem) {
            $this->applyPermissions($resultItem, $calendarId);
        }

        $this->reminderManager->applyReminders($result, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        return $result;
    }

    /**
     * Transforms recurrence data into separate field.
     *
     * @param $entity
     *
     * @return UserCalendarEventNormalizer
     */
    protected function transformRecurrenceData(&$entity)
    {
        $result = [];
        $key = Recurrence::STRING_KEY;
        $isEmpty = true;
        foreach ($entity as $field => $value) {
            if (substr($field, 0, strlen($key)) === $key) {
                unset($entity[$field]);
                $result[lcfirst(substr($field, strlen($key)))] = $value;
                $isEmpty = empty($value) ? $isEmpty : false;
            }
        }

        if (!$isEmpty) {
            $entity[$key] = $result;
        }

        return $this;
    }
}
