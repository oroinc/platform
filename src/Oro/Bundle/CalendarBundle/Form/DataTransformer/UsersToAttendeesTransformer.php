<?php

namespace Oro\Bundle\CalendarBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CalendarBundle\Entity\Attendee;

class UsersToAttendeesTransformer implements DataTransformerInterface
{
    /** @var DataTransformerInterface */
    protected $usersToIdsTransformer;

    /**
     * @param DataTransformerInterface $usersToIdsTransformer
     */
    public function __construct(DataTransformerInterface $usersToIdsTransformer)
    {
        $this->usersToIdsTransformer = $usersToIdsTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return [];
        }

        $users = $this->idsToUsers($value);

        $attendees = new ArrayCollection();
        foreach ($users as $user) {
            $attendees->add($this->userToAttendee($user));
        }

        return $attendees;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value === null) {
            return $value;
        }

        $users = $this->attendeesToUsers($value);

        $existingUsers = [];
        $encodedEmails = [];
        foreach ($users as $user) {
            if ($user->getId()) {
                $existingUsers[] = $user;
            } else {
                $encodedEmails[] = json_encode(['value' => $user->getEmail()]);
            }
        }

        return array_merge(
            $this->usersToIdsTransformer->transform($existingUsers),
            $encodedEmails
        );
    }

    /**
     * @param Collection|Attendee[] $attendees
     *
     * @return ArrayCollection|User[]
     */
    public function attendeesToUsers(Collection $attendees)
    {
        $users = new ArrayCollection();
        foreach ($attendees as $attendee) {
            $user = $attendee->getUser();
            if (!$user) {
                $user = (new User())
                    ->setEmail($attendee->getEmail())
                    ->setFirstName($attendee->getEmail());
            }

            $users->add($user);
        }

        return $users;
    }

    /**
     * @param User $user
     *
     * @return Attendee
     */
    public function userToAttendee(User $user)
    {
        $attendee = new Attendee();
        $attendee->setEmail($user->getEmail());
        if ($user->getId()) {
            $attendee->setDisplayName($user->getFullName());
            $attendee->setUser($user);
        } else {
            $attendee->setDisplayName($user->getEmail());
        }

        return $attendee;
    }

    /**
     * @param mixed $ids
     */
    protected function idsToUsers($ids)
    {
        $userIds = [];
        $emails = [];
        foreach ($ids as $userId) {
            if (is_numeric($userId)) {
                $userIds[] = $userId;
            } else {
                $emails[] = json_decode($userId)->value;
            }
        }

        return array_merge(
            $this->usersToIdsTransformer->reverseTransform($userIds),
            array_map(
                function ($email) {
                    return (new User())->setEmail($email);
                },
                $emails
            )
        );
    }
}
