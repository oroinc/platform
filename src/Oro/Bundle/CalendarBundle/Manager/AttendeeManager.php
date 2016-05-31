<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
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
     * @param Attendee[]|Collection|null $attendees
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

        return array_filter(array_map(
            function (Attendee $attendee) {
                $relatedEntity = $this->attendeeRelationManager->getRelatedEntity($attendee);
                if ($relatedEntity) {
                    return json_encode([
                        'entityClass' => ClassUtils::getClass($relatedEntity),
                        'entityId' => $this->doctrineHelper->getSingleEntityIdentifier($relatedEntity),
                    ]);
                }

                return null;
            },
            $attendees
        ));
    }
}
