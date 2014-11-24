<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class SystemCalendarProvider implements CalendarProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AbstractCalendarEventNormalizer */
    protected $calendarEventNormalizer;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param DoctrineHelper                    $doctrineHelper
     * @param AbstractCalendarEventNormalizer   $calendarEventNormalizer
     * @param AclHelper                         $aclHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        AbstractCalendarEventNormalizer $calendarEventNormalizer,
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
        /** @var SystemCalendarRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar');
        $qb = $repo->getSystemCalendarsByIdsQueryBuilder($calendarIds, false);
        $calendars = $this->aclHelper->apply($qb, 'VIEW', false)->getResult();

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
        $qb = $repo->getEventListByTimeIntervalQueryBuilder(
            $calendarId,
            $start,
            $end,
            true,
            [],
            SystemCalendar::CALENDAR_ALIAS,
            ['public' => false]
        );

        return $this->calendarEventNormalizer->getCalendarEvents(
            $calendarId,
            $this->aclHelper->apply($qb, 'VIEW', true)
        );
    }
}
