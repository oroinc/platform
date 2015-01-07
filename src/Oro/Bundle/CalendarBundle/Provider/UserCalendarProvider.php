<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;

class UserCalendarProvider extends AbstractCalendarProvider implements CalendarProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var AbstractCalendarEventNormalizer */
    protected $calendarEventNormalizer;

    /**
     * @param DoctrineHelper                  $doctrineHelper
     * @param NameFormatter                   $nameFormatter
     * @param AbstractCalendarEventNormalizer $calendarEventNormalizer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        NameFormatter $nameFormatter,
        AbstractCalendarEventNormalizer $calendarEventNormalizer
    ) {
        $this->doctrineHelper          = $doctrineHelper;
        $this->nameFormatter           = $nameFormatter;
        $this->calendarEventNormalizer = $calendarEventNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarDefaultValues($organizationId, $userId, $calendarId, array $calendarIds)
    {
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
            }
            if ($calendarId === $calendar->getId()) {
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
    public function getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections, $extraFields)
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        $qb   = $repo->getUserEventListByTimeIntervalQueryBuilder($start, $end, $extraFields);

        $visibleIds = [];
        foreach ($connections as $id => $visible) {
            if ($visible) {
                $visibleIds[] = $id;
            }
        }
        if (!empty($visibleIds)) {
            $qb
                ->andWhere('c.id IN (:visibleIds)')
                ->setParameter('visibleIds', $visibleIds);
        } else {
            $qb
                ->andWhere('1 = 0');
        }

        return $this->calendarEventNormalizer->getCalendarEvents($calendarId, $qb->getQuery());
    }

    /**
     * @param Calendar $calendar
     *
     * @return string
     */
    protected function buildCalendarName(Calendar $calendar)
    {
        $name = $calendar->getName();
        if (!$name) {
            $name = $this->nameFormatter->format($calendar->getOwner());
        }

        return $name;
    }
}
