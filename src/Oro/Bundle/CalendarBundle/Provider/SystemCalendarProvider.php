<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarPropertyRepository;
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
    public function getCalendarDefaultValues($organizationId, $userId, $calendarId, array $calendarIds)
    {
        /** @var SystemCalendarRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar');

        //@TODO: temporary return all system calendars until BAP-6566 implemented
        //$qb = $repo->getSystemCalendarsByIdsQueryBuilder($calendarIds);
        $qb = $repo->getSystemCalendarsQueryBuilder($organizationId);

        //@TODO: Fix ACL for calendars providers
        /** @var SystemCalendar[] $calendars */
        $calendars = $qb->getQuery()->getResult();

        $result = [];

        foreach ($calendars as $calendar) {
            $resultItem = [
                'calendarName'  => $calendar->getName(),
                'removable'     => false,
                'position'      => -60,
            ];
            $result[$calendar->getId()] = $resultItem;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections)
    {
        //@TODO: temporary return all system calendars until BAP-6566 implemented
        ///** @var CalendarEventRepository $repo */
        //$repo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        //$qb = $repo->getSystemEventListByTimeIntervalQueryBuilder(
        //    $calendarId,
        //    $start,
        //    $end,
        //    []
        //);

        /** @var CalendarEventRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        $qb = $repo->getSystemEventListByTimeIntervalQueryBuilder($start, $end)
            ->andWhere('c.organization = :organizationId')
            ->setParameter('organizationId', $organizationId);
        $invisibleIds = [];
        foreach ($connections as $id => $visible) {
            if (!$visible) {
                $invisibleIds[] = $id;
            }
        }
        if (!empty($invisibleIds)) {
            $qb
                ->andWhere('c.id NOT IN (:invisibleIds)')
                ->setParameter('invisibleIds', $invisibleIds);
        }

        return $this->calendarEventNormalizer->getCalendarEvents(
            $calendarId,
            //@TODO: Fix ACL for calendars providers
            $qb->getQuery()
        );
    }
}
