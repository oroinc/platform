<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\PropertyAccess\PropertyAccessor;

class UserCalendarProvider extends AbstractCalendarProvider
{
    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var AbstractCalendarEventNormalizer */
    protected $calendarEventNormalizer;

    /** @var StrategyInterface  */
    protected $recurrenceModel;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * UserCalendarProvider constructor.
     *
     * @param DoctrineHelper $doctrineHelper
     * @param EntityNameResolver $entityNameResolver
     * @param AbstractCalendarEventNormalizer $calendarEventNormalizer
     * @param Recurrence $recurrenceModel
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityNameResolver $entityNameResolver,
        AbstractCalendarEventNormalizer $calendarEventNormalizer,
        Recurrence $recurrenceModel
    ) {
        parent::__construct($doctrineHelper);
        $this->entityNameResolver      = $entityNameResolver;
        $this->calendarEventNormalizer = $calendarEventNormalizer;
        $this->recurrenceModel         = $recurrenceModel;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarDefaultValues($organizationId, $userId, $calendarId, array $calendarIds)
    {
        if (empty($calendarIds)) {
            return [];
        }

        $qb = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar')
            ->createQueryBuilder('o')
            ->select('o, owner')
            ->innerJoin('o.owner', 'owner');
        $qb->where($qb->expr()->in('o.id', $calendarIds));

        $result = [];

        /** @var Entity\Calendar[] $calendars */
        $calendars = $qb->getQuery()->getResult();
        foreach ($calendars as $calendar) {
            $resultItem = [
                'calendarName' => $this->buildCalendarName($calendar),
                'userId'       => $calendar->getOwner()->getId()
            ];
            // prohibit to remove the current calendar from the list of connected calendars
            if ($calendar->getId() === $calendarId) {
                $resultItem['removable'] = false;
                $resultItem['canAddEvent']    = true;
                $resultItem['canEditEvent']   = true;
                $resultItem['canDeleteEvent'] = true;
            }
            $result[$calendar->getId()] = $resultItem;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarEvents(
        $organizationId,
        $userId,
        $calendarId,
        $start,
        $end,
        $connections,
        $extraFields = []
    ) {
        /** @var CalendarEventRepository $repo */
        $repo        = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        $extraFields = $this->filterSupportedFields($extraFields, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');
        $qb          = $repo->getUserEventListByTimeIntervalQueryBuilder($start, $end, [], $extraFields);

        $visibleIds = [];
        foreach ($connections as $id => $visible) {
            if ($visible) {
                $visibleIds[] = $id;
            }
        }
        if ($visibleIds) {
            $qb
                ->andWhere('c.id IN (:visibleIds)')
                ->setParameter('visibleIds', $visibleIds);
        } else {
            $qb
                ->andWhere('1 = 0');
        }

        $items = $this->calendarEventNormalizer->getCalendarEvents($calendarId, $qb->getQuery());
        $items = $this->getExpandedRecurrences($items, $start, $end);

        return $items;
    }

    /**
     * @param Entity\Calendar $calendar
     *
     * @return string
     */
    protected function buildCalendarName(Entity\Calendar $calendar)
    {
        return $calendar->getName() ?: $this->entityNameResolver->getName($calendar->getOwner());
    }

    /**
     * Returns transformed and expanded list with respected recurring events based on unprocessed events in $rawItems
     * and date range.
     *
     * @param array $rawItems
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    protected function getExpandedRecurrences(array $rawItems, \DateTime $start, \DateTime $end)
    {
        $regularEvents = $this->filterRegularEvents($rawItems);
        $recurringExceptionEvents = $this->filterRecurringExceptionEvents($rawItems);
        $recurringOccurrenceEvents = $this->filterRecurringOccurrenceEvents($rawItems, $start, $end);

        return $this->mergeRegularAndRecurringEvents(
            $regularEvents,
            $recurringOccurrenceEvents,
            $recurringExceptionEvents
        );
    }

    /**
     * Returns list of all regular and not recurring events. Filters processed events from $items.
     *
     * @param array $items
     *
     * @return array
     */
    protected function filterRegularEvents(array &$items)
    {
        $events = [];
        foreach ($items as $index => $item) {
            if (empty($item[Recurrence::STRING_KEY]) && empty($item['recurringEventId'])) {
                $events[] = $item;
                unset($items[$index]);
            }
        }

        return $events;
    }

    /**
     * Returns list of all events which represent exception of recurring event. Filters processed events from $items.
     *
     * @param array $items
     *
     * @return array
     */
    protected function filterRecurringExceptionEvents(array &$items)
    {
        $exceptions = [];
        foreach ($items as $index => $item) {
            if (empty($item[Recurrence::STRING_KEY]) &&
                !empty($item['recurringEventId']) &&
                !empty($item['originalStart'])
            ) {
                unset($items[$index]);
                $exceptions[] = $item;
            }
        }

        return $exceptions;
    }

    /**
     * For each recurring event creates records representing events of recurring occurrence for [$start, $end] range.
     * Returns merged list of all such events. Filters processed events from $items.
     *
     * @param array $items
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException
     * @throws \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    protected function filterRecurringOccurrenceEvents(array &$items, \DateTime $start, \DateTime $end)
    {
        $key = Recurrence::STRING_KEY;
        $propertyAccessor = $this->getPropertyAccessor();
        $occurrences = [];
        $dateFields = ['startTime', 'endTime', 'calculatedEndTime'];
        foreach ($items as $index => $item) {
            if (!empty($item[$key]) && empty($item['recurringEventId'])) {
                unset($items[$index]);
                $recurrence = new Entity\Recurrence();
                foreach ($item[$key] as $field => $value) {
                    $value = in_array($field, $dateFields, true) && $value !== null
                        ? new \DateTime($value, new \DateTimeZone('UTC'))
                        : $value;
                    if ($field !== 'id') {
                        $propertyAccessor->setValue($recurrence, $field, $value);
                    }
                }
                unset($item[$key]['calculatedEndTime']);

                //set timezone for all datetime values,
                // to make sure all occurrences are calculated in the time zone in which the recurrence is created
                $recurrenceTimezone = new \DateTimeZone($recurrence->getTimeZone());
                $recurrence->getStartTime()->setTimezone($recurrenceTimezone);
                $recurrence->getCalculatedEndTime()->setTimezone($recurrenceTimezone);
                $start->setTimezone($recurrenceTimezone);
                $end->setTimezone($recurrenceTimezone);

                $occurrenceDates = $this->recurrenceModel->getOccurrences($recurrence, $start, $end);
                $newStartDate = new \DateTime($item['start']);
                $calendarEvent = new Entity\CalendarEvent();
                $calendarEvent->setStart(clone $newStartDate);
                $recurrence->setCalendarEvent($calendarEvent);
                $newStartDate->setTimezone($recurrenceTimezone);
                $itemEndDate = new \DateTime($item['end']);
                $itemEndDate->setTimezone($recurrenceTimezone);
                $duration = $itemEndDate->diff($newStartDate);
                $timeZone = new \DateTimeZone('UTC');
                foreach ($occurrenceDates as $occurrenceDate) {
                    $newItem = $item;
                    $newStartDate->setTimezone($recurrenceTimezone);
                    $newStartDate->setDate(
                        $occurrenceDate->format('Y'),
                        $occurrenceDate->format('m'),
                        $occurrenceDate->format('d')
                    );
                    $newStartDate->setTimezone($timeZone);
                    $newItem['start'] = $newStartDate->format('c');
                    $newItem['recurrencePattern'] = $this->recurrenceModel->getTextValue($recurrence);
                    $endDate = new \DateTime(
                        sprintf(
                            '+%s minute +%s hour +%s day +%s month +%s year %s',
                            $duration->format('%i'),
                            $duration->format('%h'),
                            $duration->format('%d'),
                            $duration->format('%m'),
                            $duration->format('%y'),
                            $newStartDate->format('c')
                        ),
                        $timeZone
                    );
                    $newItem['end'] = $endDate->format('c');
                    $newItem['removable'] = false;
                    $newItem['startEditable'] = false;
                    $newItem['durationEditable'] = false;
                    $occurrences[] = $newItem;
                }
            }
        }

        return $occurrences;
    }

    /**
     * Merges all previously filtered events.
     *
     * Result will contain:
     * $regularEvents + ($recurringOccurrenceEvents - $recurringExceptionEvents) + $recurringExceptionEvents
     *
     * @param array $regularEvents
     * @param array $recurringOccurrenceEvents
     * @param array $recurringExceptionEvents
     *
     * @return array
     */
    protected function mergeRegularAndRecurringEvents(
        array $regularEvents,
        array $recurringOccurrenceEvents,
        array $recurringExceptionEvents
    ) {
        $recurringEvents = [];

        foreach ($recurringOccurrenceEvents as $occurrence) {
            $exceptionFound = false;
            foreach ($recurringExceptionEvents as $key => $exception) {
                $originalStartTime = new \DateTime($exception['originalStart']);
                $start = new \DateTime($occurrence['start']);
                if ((int)$exception['recurringEventId'] === (int)$occurrence['id'] &&
                    $originalStartTime->getTimestamp() === $start->getTimestamp()
                ) {
                    $exceptionFound = true;
                    if (empty($exception['isCancelled'])) {
                        $exception['recurrencePattern'] = $occurrence['recurrencePattern'];
                        $recurringEvents[] = $exception;
                    }
                    unset($recurringExceptionEvents[$key]);
                }
            }

            if (!$exceptionFound) {
                $recurringEvents[] = $occurrence;
            }
        }

        return array_merge($regularEvents, $recurringEvents, $recurringExceptionEvents);
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
