<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class AttendeeManager
{
    /** @var ConverterInterface */
    protected $usersConverter;

    /** @var UsersToAttendeesTransformer */
    protected $usersToAttendeesTransformer;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ConverterInterface $usersConverter
     * @param UsersToAttendeesTransformer $usersToAttendeesTransformer
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        ConverterInterface $usersConverter,
        UsersToAttendeesTransformer $usersToAttendeesTransformer,
        SecurityFacade $securityFacade
    ) {
        $this->usersConverter = $usersConverter;
        $this->usersToAttendeesTransformer = $usersToAttendeesTransformer;
        $this->securityFacade = $securityFacade;
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
