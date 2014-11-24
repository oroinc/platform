<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class SystemCalendarProvider implements CalendarProviderInterface
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
        $qb = $repo->getSystemCalendarsByIdsQueryBuilder($calendarIds);
        //@TODO: Fix ACL for calendars providers
        /** @var SystemCalendar[] $calendars */
        $calendars = $qb->getQuery()->getResult();

        $result = [];

        foreach ($calendars as $calendar) {
            $resultItem = [
                'calendarName'  => $calendar->getName(),
                'position'      => -60,
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
        $qb = $repo->getSystemEventListByTimeIntervalQueryBuilder(
            $calendarId,
            $start,
            $end,
            []
        );

        return $this->calendarEventNormalizer->getCalendarEvents(
            $calendarId,
            //@TODO: Fix ACL for calendars providers
            $qb->getQuery()
        );
    }
}
