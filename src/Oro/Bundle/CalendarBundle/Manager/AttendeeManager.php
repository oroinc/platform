<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
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
     * @param Attendee[]|\Traversable $attendees
     *
     * @return array
     */
    public function attendeesToAutocompleteData($attendees)
    {
        $transformedData = $this->usersToAttendeesTransformer->attendeesToUsers($attendees);
        $disableUserRemoval = !$this->securityFacade->isGranted('oro_user_user_view');

        $result = [];
        foreach ($transformedData as $k => $item) {
            $converted = $this->usersConverter->convertItem($item);

            if (!$this->isAttendeeRemovable($attendees[$k], $disableUserRemoval)) {
                $converted['locked'] = true;
            }

            $result[] = $converted;
        }

        return $result;
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

    /**
     * @param Attendee $attendee
     * @param bool     $disableUserRemoval
     *
     * @return bool
     */
    protected function isAttendeeRemovable(Attendee $attendee, $disableUserRemoval = false)
    {
        return !$disableUserRemoval && $attendee->getUser() instanceof User;
    }
}
