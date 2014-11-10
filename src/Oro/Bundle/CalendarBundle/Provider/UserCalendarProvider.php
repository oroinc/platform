<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;

class UserCalendarProvider implements CalendarProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var CalendarEventNormalizer */
    protected $calendarEventNormalizer;

    /**
     * @param DoctrineHelper          $doctrineHelper
     * @param NameFormatter           $nameFormatter
     * @param CalendarEventNormalizer $calendarEventNormalizer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        NameFormatter $nameFormatter,
        CalendarEventNormalizer $calendarEventNormalizer
    ) {
        $this->doctrineHelper          = $doctrineHelper;
        $this->nameFormatter           = $nameFormatter;
        $this->calendarEventNormalizer = $calendarEventNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarDefaultValues($userId, $calendarId, array $calendarIds)
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
                'calendarName' => $this->buildCalendarName($calendar)
            ];
            // prohibit to remove the current calendar from the list of connected calendars
            if ($calendar->getId() === $calendarId) {
                $resultItem['removable'] = false;
            }
            $result[$calendar->getId()] = $resultItem;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarEvents($userId, $calendarId, $start, $end, $subordinate)
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        $qb   = $repo->getEventListByTimeIntervalQueryBuilder($calendarId, $start, $end, $subordinate);

        return $this->calendarEventNormalizer->getCalendarEvents($calendarId, $qb);
    }

    /**
     * @param Calendar $calendar
     *
     * @return string
     */
    protected function buildCalendarName(Calendar $calendar)
    {
        $name = $calendar->getName();
        if (empty($name)) {
            $name = $this->nameFormatter->format($calendar->getOwner());
        }

        return $name;
    }
}
