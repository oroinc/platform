<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

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
     * @param string $id
     *
     * @return Attendee[]
     */
    public function loadAttendeesByCalendarEventId($id)
    {
        return $this->doctrineHelper
            ->getEntityRepository('OroCalendarBundle:Attendee')
            ->findBy(['calendarEvent'=>$id]);
    }
}
