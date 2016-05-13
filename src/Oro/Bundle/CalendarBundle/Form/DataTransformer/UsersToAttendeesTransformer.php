<?php

namespace Oro\Bundle\CalendarBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;

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

        $userIds = [];
        $emails = [];
        foreach ($value as $userId) {
            if (is_numeric($userId)) {
                $userIds[] = $userId;
            } else {
                $emails[] = json_decode($userId)->value;
            }
        }

        $users = array_merge(
            $this->usersToIdsTransformer->reverseTransform($userIds),
            array_map(
                function ($email) {
                    return (new User())->setEmail($email);
                },
                $emails
            )
        );

        $attendees = new ArrayCollection();
        foreach ($users as $user) {
            $attendee = new Attendee();
            $attendee->setDisplayName($user->getFullName());
            $attendee->setEmail($user->getEmail());
            if ($user->getId()) {
                $attendee->setUser($user);
            }
            $attendees->add($attendee);
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

        $users = new ArrayCollection();
        foreach ($value as $attendee) {
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
}
