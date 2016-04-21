<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

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

        $this->transformRecurrences($items, $start, $end);

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
     * Transforms recurrence rules into entity items
     *
     * @param array $items
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return self
     */
    protected function transformRecurrences(array &$items, $start, $end)
    {
        $key = Recurrence::STRING_KEY;
        $propertyAccessor = $this->getPropertyAccessor();
        foreach ($items as $index => $item) {
            if (empty($item[$key])) {
                continue;
            }

            $recurrence = new Recurrence();
            $exceptions = empty($item[$key]['exceptions']) ? [] : $item[$key]['exceptions'];
            unset($item[$key]['exceptions']);
            foreach ($item[$key] as $field => $value) {
                $value = in_array($field, ['startTime', 'endTime']) ? new \DateTime($value) : $value;
                $propertyAccessor->setValue($recurrence, $field, $value);
            }
            $occurrences = $this->recurrenceStrategy->getOccurrences($recurrence, $start, $end);
            //unset recurrence values, because we don't need it for duplication
            unset($item[$key]);
            /** @var \DateTime $occurrence */
            foreach ($occurrences as $occurrence) {
                $newItem = $item;
                $newItem['start'] = $occurrence->format('c');
                $endDate = new \DateTime($newItem['end']);
                $endDate->setDate($occurrence->format('Y'), $occurrence->format('m'), $occurrence->format('d'));
                $newItem['end'] = $endDate->format('c');
                $exception = $this->getRecurrenceException($occurrence, $exceptions);
                $items[] = $exception ? array_merge($newItem, $exception) : $newItem;
            }
            //remove original item with recurrence, because it was calculated with recurrence rules
            unset($items[$index]);
        }

        return $this;
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

    /**
     * Returns exception data for recurrence item.
     *
     * @param \DateTime $occurrence
     * @param array $exceptions
     *
     * @return null|array
     */
    protected function getRecurrenceException(\DateTime $occurrence, $exceptions)
    {
        foreach ($exceptions as $exception) {
            //if original date of exception is the same with occurrence
            if ($occurrence->diff(new \DateTime($exception['originalDate']))->format('%a') == 0) {
                //don't need this value in result
                unset($exception['originalDate']);

                return $exception;
            }
        }

        return null;
    }
}
