<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\ORM\AbstractQuery;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Component\PhpUtils\ArrayUtil;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
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
        $this->addRecurrenceExceptions($result);
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

        if ($recurrence = $event->getRecurrence()) {
            $extraValues[Recurrence::STRING_KEY] = [
                'recurrenceType' => $recurrence->getRecurrenceType(),
                'interval' => $recurrence->getInterval(),
                'instance' => $recurrence->getInstance(),
                'dayOfWeek' => $recurrence->getDayOfWeek(),
                'dayOfMonth' => $recurrence->getDayOfMonth(),
                'monthOfYear' => $recurrence->getMonthOfYear(),
                'startTime' => $recurrence->getStartTime(),
                'endTime' => $recurrence->getStartTime(),
                // @TODO fix typo 'occurences' => 'occurrences' after it will be fixed in plugin.
                'occurences' => $recurrence->getOccurrences()
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
    protected function applyAdditionalData(&$items, $calendarId)
    {
        $parentEventIds = $this->getParentEventIds($items);
        if ($parentEventIds) {
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
        $this->addRecurrenceExceptions($result);
        foreach ($result as &$resultItem) {
            $this->applyPermissions($resultItem, $calendarId);
        }

        $this->reminderManager->applyReminders($result, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        return $result;
    }

    /**
     * Gets recurrence exceptions from DB and adds it to appropriate recurrence items.
     *
     * @param array $items
     *
     * @return self
     */
    protected function addRecurrenceExceptions(&$items)
    {
        $exceptionParentIds = ArrayUtil::arrayColumn($items, 'id');

        /** @var CalendarEventRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        $exceptions = $repository->getRecurrenceExceptionsByParentIds($exceptionParentIds)
            ->getQuery()
            ->getArrayResult();

        $key = Recurrence::STRING_KEY;
        foreach ($exceptions as $exception) {
            foreach ($items as $index => $item) {
                if ($item['id'] == $exception['parentExceptionId'] && !empty($item[$key])) {
                    //don't need this value in result
                    unset($exception['parentExceptionId']);
                    if (empty($items[$index][$key]['exceptions'])) {
                        $items[$index][$key]['exceptions'] = [];
                    }
                    $items[$index][$key]['exceptions'][] = $this->transformEntity($exception);
                    break;
                }
            }
        }

        return $this;
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
                $result[lcfirst(substr($field, strlen($key)))] = $value;
            }
        }

        if (!empty($result)) {
            $entity[$key] = $result;
        }

        return $this;
    }
}
