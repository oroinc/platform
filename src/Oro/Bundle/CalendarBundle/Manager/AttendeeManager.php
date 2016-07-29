<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class AttendeeManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param AttendeeRelationManager $attendeeRelationManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, AttendeeRelationManager $attendeeRelationManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->attendeeRelationManager = $attendeeRelationManager;
    }

    /**
     * @param int $id
     *
     * @return Attendee[]
     */
    public function loadAttendeesByCalendarEventId($id)
    {
        return $this->doctrineHelper
            ->getEntityRepository('OroCalendarBundle:Attendee')
            ->findBy(['calendarEvent' => $id]);
    }

    /**
     * @param Attendee[]|Collection $attendees
     *
     * @return array
     */
    public function createAttendeeExclusions($attendees)
    {
        if (!$attendees) {
            return [];
        }

        if ($attendees instanceof Collection) {
            $attendees = $attendees->toArray();
        }

        return array_reduce(
            $attendees,
            function (array $result, Attendee $attendee) {
                $relatedEntity = $this->attendeeRelationManager->getRelatedEntity($attendee);
                if (!$relatedEntity) {
                    return $result;
                }

                $key = json_encode([
                    'entityClass' => ClassUtils::getClass($relatedEntity),
                    'entityId' => $this->doctrineHelper->getSingleEntityIdentifier($relatedEntity),
                ]);

                $val = json_encode([
                    'entityClass' => 'Oro\Bundle\CalendarBundle\Entity\Attendee',
                    'entityId' => $attendee->getId(),
                ]);

                $result[$key] = $val;

                return $result;
            },
            []
        );
    }

    /**
     * @param array $calendarEventIds
     *
     * @return array
     */
    public function getAttendeeListsByCalendarEventIds(array $calendarEventIds)
    {
        if (!$calendarEventIds) {
            return [];
        }

        /** @var CalendarEventRepository $calendarEventRepository */
        $calendarEventRepository = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        $parentToChildren = $calendarEventRepository->getParentEventIds($calendarEventIds);

        /** @var AttendeeRepository $attendeeRepository */
        $attendeeRepository = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Attendee');
        $qb = $attendeeRepository->createAttendeeListsQb(array_keys($parentToChildren));
        $this->attendeeRelationManager->addRelatedEntityInfo($qb);

        $queryResult = $qb
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($queryResult as $row) {
            $parentCalendarEventId = $row['calendarEventId'];
            unset($row['calendarEventId']);
            foreach ($parentToChildren[$parentCalendarEventId] as $childId) {
                $result[$childId][] = $row;
            }
        }

        return $result += array_fill_keys($calendarEventIds, []);
    }
}
