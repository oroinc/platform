<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\UserBundle\Entity\User;

class AttendeeRelationManager
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var NameFormatter */
    protected $nameFormatter;

    /**
     * @param ManagerRegistry $registry
     * @param NameFormatter   $nameFormatter
     */
    public function __construct(ManagerRegistry $registry, NameFormatter $nameFormatter)
    {
        $this->registry = $registry;
        $this->nameFormatter = $nameFormatter;
    }

    /**
     * @param object $relatedEntity
     *
     * @return Attendee|null
     */
    public function createAttendee($relatedEntity)
    {
        if (!$relatedEntity instanceof User) {
            return null;
        }

        return (new Attendee())
            ->setDisplayName($this->nameFormatter->format($relatedEntity))
            ->setEmail($relatedEntity->getEmail())
            ->setUser($relatedEntity);
    }

    /**
     * @param Attendee $attendee
     *
     * @return object|null
     */
    public function getRelatedEntity(Attendee $attendee)
    {
        return $attendee->getUser();
    }

    /**
     * @param Attendee[]|\Traversable $attendees
     */
    public function bindAttendees($attendees)
    {
        $unboundAttendeesByEmail = $this->getUnboundAttendeesByEmail($attendees);
        if (!$unboundAttendeesByEmail) {
            return;
        }

        $users = $this->registry
            ->getRepository('OroUserBundle:User')
            ->findUsersByEmails(array_keys($unboundAttendeesByEmail));

        $this->bindUsersToAttendees($users, $unboundAttendeesByEmail);
    }

    /**
     * @param User[]   $users
     * @param string[] $unboundAttendeesByEmail
     */
    protected function bindUsersToAttendees(array $users, array $unboundAttendeesByEmail)
    {
        foreach ($users as $user) {
            if (isset($unboundAttendeesByEmail[$user->getEmail()])) {
                $this->bindUser($user, $unboundAttendeesByEmail[$user->getEmail()]);
                unset($unboundAttendeesByEmail[$user->getEmail()]);
            }

            foreach ($user->getEmails() as $emailEntity) {
                $email = $emailEntity->getEmail();
                if (isset($unboundAttendeesByEmail[$email])) {
                    $this->bindUser($user, $unboundAttendeesByEmail[$email]);
                    unset($unboundAttendeesByEmail[$email]);
                }
            }
        }
    }

    /**
     * @param User $user
     * @param Attendee $attendee
     */
    protected function bindUser(User $user, Attendee $attendee)
    {
        $attendee->setUser($user);
        if (!$attendee->getDisplayName()) {
            $attendee->setDisplayName($this->nameFormatter->format($user));
        }
    }

    /**
     * @param Attendee[]|\Traversable $attendees
     *
     * @return Attendee[]
     */
    protected function getUnboundAttendeesByEmail($attendees)
    {
        $unbound = [];
        foreach ($attendees as $attendee) {
            if (!$attendee->getEmail() || $this->getRelatedEntity($attendee)) {
                continue;
            }

            $unbound[$attendee->getEmail()] = $attendee;
        }

        return $unbound;
    }
}
