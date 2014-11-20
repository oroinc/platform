<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class SystemCalendarProvider implements CalendarProviderInterface
{
    const CALENDAR_KIND = 'system';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var CalendarEventNormalizer */
    protected $calendarEventNormalizer;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param DoctrineHelper          $doctrineHelper
     * @param CalendarEventNormalizer $calendarEventNormalizer
     * @param AclHelper               $aclHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        CalendarEventNormalizer $calendarEventNormalizer,
        AclHelper $aclHelper
    ) {
        $this->doctrineHelper          = $doctrineHelper;
        $this->calendarEventNormalizer = $calendarEventNormalizer;
        $this->aclHelper               = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarDefaultValues($userId, $calendarId, array $calendarIds)
    {
        $query = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar')
            ->getCalendarsByIdsQuery($calendarIds, false);
        $calendars = $this->aclHelper->apply($query, 'VIEW', false)->getResult();

        $result = [];

        foreach ($calendars as $calendar) {
            $resultItem = [
                'calendarName' => $this->buildCalendarName($calendar)
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
        /** @var QueryBuilder $qb */
        $qb = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent')
            ->getEventListByTimeIntervalQueryBuilder(
                $calendarId,
                $start,
                $end,
                $subordinate,
                ['public' => false],
                self::CALENDAR_KIND
            );

        return $this->calendarEventNormalizer->getCalendarEvents($calendarId, $qb);
    }

    /**
     * @param SystemCalendar $calendar
     *
     * @return string
     */
    protected function buildCalendarName(SystemCalendar $calendar)
    {
        $name = $calendar->getName();
        if (!$name) {
            $name = $calendar->getOwner()->getName();
        }

        return $name;
    }
}
