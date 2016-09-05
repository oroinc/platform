<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\UserBundle\Entity\User;

class AttendeeRelationManager
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var DQLNameFormatter */
    protected $dqlNameFormatter;

    /**
     * @param ManagerRegistry $registry
     * @param NameFormatter   $nameFormatter
     * @param DQLNameFormatter $dqlNameFormatter
     */
    public function __construct(
        ManagerRegistry $registry,
        NameFormatter $nameFormatter,
        DQLNameFormatter $dqlNameFormatter
    ) {
        $this->registry = $registry;
        $this->nameFormatter = $nameFormatter;
        $this->dqlNameFormatter = $dqlNameFormatter;
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
     * @param Attendee $attendee
     *
     * @return string
     */
    public function getRelatedDisplayName(Attendee $attendee)
    {
        return $attendee->getUser() ? $this->nameFormatter->format($attendee->getUser()) : $attendee->getDisplayName();
    }

    /**
     * Adds fullName column with text representation of attendee into the result
     *
     * @param QueryBuilder $qb
     */
    public function addRelatedEntityInfo(QueryBuilder $qb)
    {
        $userName = $this->dqlNameFormatter->getFormattedNameDQL('user', 'Oro\Bundle\UserBundle\Entity\User');

        $qb
            ->addSelect(sprintf('%s AS fullName, user.id AS userId', $userName))
            ->leftJoin('attendee.user', 'user');
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
            $normalizedEmail = $this->normalizeEmail($user->getEmail());
            if (isset($unboundAttendeesByEmail[$normalizedEmail])) {
                $this->bindUser($user, $unboundAttendeesByEmail[$normalizedEmail]);
                unset($unboundAttendeesByEmail[$normalizedEmail]);
            }

            foreach ($user->getEmails() as $emailEntity) {
                $normalizedEmail = $this->normalizeEmail($emailEntity->getEmail());
                if (isset($unboundAttendeesByEmail[$normalizedEmail])) {
                    $this->bindUser($user, $unboundAttendeesByEmail[$normalizedEmail]);
                    unset($unboundAttendeesByEmail[$normalizedEmail]);
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

            $unbound[$this->normalizeEmail($attendee->getEmail())] = $attendee;
        }

        return $unbound;
    }

    /**
     * @param string|null $email
     *
     * @return string|null
     */
    protected function normalizeEmail($email)
    {
        if (!$email) {
            return $email;
        }

        return strtolower($email);
    }
}
