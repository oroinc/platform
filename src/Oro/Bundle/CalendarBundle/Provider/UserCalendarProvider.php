<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\StrategyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Component\PropertyAccess\PropertyAccessor;

class UserCalendarProvider extends AbstractCalendarProvider
{
    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var AbstractCalendarEventNormalizer */
    protected $calendarEventNormalizer;

    /** @var StrategyInterface  */
    protected $recurrenceStrategy;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * UserCalendarProvider constructor.
     *
     * @param DoctrineHelper $doctrineHelper
     * @param EntityNameResolver $entityNameResolver
     * @param AbstractCalendarEventNormalizer $calendarEventNormalizer
     * @param StrategyInterface $recurrenceStrategy
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityNameResolver $entityNameResolver,
        AbstractCalendarEventNormalizer $calendarEventNormalizer,
        StrategyInterface $recurrenceStrategy
    ) {
        parent::__construct($doctrineHelper);
        $this->entityNameResolver      = $entityNameResolver;
        $this->calendarEventNormalizer = $calendarEventNormalizer;
        $this->recurrenceStrategy       = $recurrenceStrategy;
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

        /** @var Calendar[] $calendars */
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

        $this->addRecurrencesConditions($qb, $start, $end);

        $items = $this->calendarEventNormalizer->getCalendarEvents($calendarId, $qb->getQuery());
        $items = $this->getExpandedRecurrences($items, $start, $end);

        return $items;
    }

    /**
     * @param Calendar $calendar
     *
     * @return string
     */
    protected function buildCalendarName(Calendar $calendar)
    {
        return $calendar->getName() ?: $this->entityNameResolver->getName($calendar->getOwner());
    }

    /**
     * @param array $items
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    protected function getExpandedRecurrences(array $items, \DateTime $start, \DateTime $end)
    {
        $regularEvents = $this->getRegularEvents($items);
        $exceptions = $this->getExceptions($items);
        $occurrences = $this->getOccurrences($items, $start, $end);

        return array_merge($regularEvents, $this->getMergedOccurrencesWithExceptions($occurrences, $exceptions));
    }

    /**
     * @param array $items
     *
     * @return array
     */
    protected function getRegularEvents(array $items)
    {
        $events = [];
        foreach ($items as $item) {
            if (empty($item[Recurrence::STRING_KEY]) && empty($item['recurringEventId'])) {
                $events[] = $item;
            }
        }

        return $events;
    }

    /**
     * @param array $items
     *
     * @return array
     */
    protected function getExceptions(array $items)
    {
        $exceptions = [];
        foreach ($items as $item) {
            if (empty($item[Recurrence::STRING_KEY]) &&
                !empty($item['recurringEventId']) &&
                !empty($item['originalStart'])
            ) {
                $exceptions[] = $item;
            }
        }

        return $exceptions;
    }

    /**
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
    protected function getOccurrences(array $items, \DateTime $start, \DateTime $end)
    {
        $key = Recurrence::STRING_KEY;
        $propertyAccessor = $this->getPropertyAccessor();
        $occurrences = [];
        foreach ($items as $item) {
            if (!empty($item[$key]) && empty($item['recurringEventId'])) {
                $recurrence = new Recurrence();
                foreach ($item[$key] as $field => $value) {
                    $value = in_array($field, ['startTime', 'endTime'], true) ? new \DateTime($value) : $value;
                    if ($field !== 'id') {
                        $propertyAccessor->setValue($recurrence, $field, $value);
                    }
                }
                $occurrenceDates = $this->recurrenceStrategy->getOccurrences($recurrence, $start, $end);
                foreach ($occurrenceDates as $occurrenceDate) {
                    $newItem = $item;
                    $newItem['recurrencePattern'] = $this->recurrenceStrategy->getRecurrencePattern($recurrence);
                    $newItem['start'] = $occurrenceDate->format('c');
                    $endDate = new \DateTime($newItem['end']);
                    $endDate->setDate(
                        $occurrenceDate->format('Y'),
                        $occurrenceDate->format('m'),
                        $occurrenceDate->format('d')
                    );
                    $newItem['end'] = $endDate->format('c');
                    $occurrences[] = $newItem;
                }
            }
        }

        return $occurrences;
    }

    /**
     * @param array $occurrences
     * @param array $exceptions
     *
     * @return array
     */
    protected function getMergedOccurrencesWithExceptions(array $occurrences, array $exceptions)
    {
        foreach ($occurrences as $oKey => &$occurrence) {
            foreach ($exceptions as $eKey => $exception) {
                if ((int)$exception['recurringEventId'] === (int)$occurrence['id'] &&
                    (new \DateTime($exception['originalStart'])) == (new \DateTime($occurrence['start']))
                ) {
                    if ($exception['isCancelled']) {
                        unset($occurrences[$oKey]);
                    } else {
                        $occurrence = $exception;
                    }
                    unset($exceptions[$eKey]);
                }
            }
        }
        unset($occurrence);
        $occurrences = empty($occurrences) ? [] : $occurrences;
        if (!empty($exceptions)) {
            $occurrences = array_merge($occurrences, $exceptions);
        }

        return $occurrences;
    }

    /**
     * Adds conditions for getting recurrence events that could be out of filtering dates.
     *
     * @param QueryBuilder $queryBuilder
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return self
     */
    protected function addRecurrencesConditions(QueryBuilder $queryBuilder, $startDate, $endDate)
    {
        //add condition that recurrence dates and filter dates are crossing
        $expr = $queryBuilder->expr();
        $queryBuilder->orWhere(
            $expr->andX(
                $expr->lte('r.startTime', ':endDate'),
                $expr->gte('r.endTime', ':startDate')
            )
        )
        ->orWhere(
            $expr->andX(
                $expr->isNotNull('e.originalStart'),
                $expr->lte('e.originalStart', ':endDate'),
                $expr->gte('e.originalStart', ':startDate')
            )
        );
        $queryBuilder->setParameter('startDate', $startDate);
        $queryBuilder->setParameter('endDate', $endDate);

        return $this;
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
