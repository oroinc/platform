<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Doctrine\Common\Collections\Collection;

class AttendeeManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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
     */
    public function createAttendeeExclusions($attendees)
    {
        return array_filter(array_map(
            function (Attendee $attendee) {
                $user = $attendee->getUser();
                if ($user) {
                    return json_encode([
                        'entityClass' => 'Oro\Bundle\UserBundle\Entity\User',
                        'entityId' => $user->getId(),
                    ]);
                }

                return null;
            },
            $attendees
        ));
    }
}
