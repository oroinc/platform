<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class PublicSystemCalendarProvider implements CalendarProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AbstractCalendarEventNormalizer */
    protected $calendarEventNormalizer;

    /**
     * @param DoctrineHelper                    $doctrineHelper
     * @param AbstractCalendarEventNormalizer   $calendarEventNormalizer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        AbstractCalendarEventNormalizer $calendarEventNormalizer
    ) {
        $this->doctrineHelper          = $doctrineHelper;
        $this->calendarEventNormalizer = $calendarEventNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarDefaultValues($userId, $calendarId, array $calendarIds)
    {
        /** @var SystemCalendarRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar');
        $qb = $repo->getPublicCalendarsQueryBuilder();
        /** @var SystemCalendar $calendars */
        $calendars = $qb->getQuery()->getResult();

        $result = [];

        foreach ($calendars as $calendar) {
            $resultItem = [
                'calendarName'  => $name = $calendar->getName(),
                'removable'     => false,
                'position'      => -80,
            ];
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
        $qb = $repo->getPublicEventListByTimeIntervalQueryBuilder($start, $end, []);

        return $this->calendarEventNormalizer->getCalendarEvents($calendarId, $qb->getQuery());
    }
}
