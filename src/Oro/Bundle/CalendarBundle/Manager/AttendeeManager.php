<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class AttendeeManager
{
    /** @var ConverterInterface */
    protected $usersConverter;

    /** @var UsersToAttendeesTransformer */
    protected $usersToAttendeesTransformer;

    /** @var SecurityFacade */
    protected $securityFacade;
    
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ConverterInterface          $usersConverter
     * @param UsersToAttendeesTransformer $usersToAttendeesTransformer
     * @param SecurityFacade              $securityFacade
     * @param DoctrineHelper              $doctrineHelper
     */
    public function __construct(
        ConverterInterface $usersConverter,
        UsersToAttendeesTransformer $usersToAttendeesTransformer,
        SecurityFacade $securityFacade,
        DoctrineHelper $doctrineHelper
    ) {
        $this->usersConverter              = $usersConverter;
        $this->usersToAttendeesTransformer = $usersToAttendeesTransformer;
        $this->securityFacade              = $securityFacade;
        $this->doctrineHelper              = $doctrineHelper;
    }

    /**
     * @param string $id
     *
     * @return Attendee[]
     */
    public function loadAttendeesByCalendarEventId($id)
    {
        /** @var AttendeeRepository $attendeeRepository */
        $attendeeRepository = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Attendee');

        return $attendeeRepository->getAttendeesByCalendarEventId($id);
    }
}
