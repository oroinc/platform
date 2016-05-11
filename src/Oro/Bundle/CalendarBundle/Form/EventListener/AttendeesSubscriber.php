<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Collection;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\UserBundle\Entity\User;

class AttendeesSubscriber implements EventSubscriberInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT => ['postSubmit', -100],
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $attendees = $event->getData();
        if (!$attendees) {
            return;
        }

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
     * @param User[] $users
     * @param string[] $unboundAttendeesByEmail
     */
    protected function bindUsersToAttendees(array $users, array $unboundAttendeesByEmail)
    {
        foreach ($users as $user) {
            if (isset($unboundAttendeesByEmail[$user->getEmail()])) {
                $unboundAttendeesByEmail[$user->getEmail()]->setUser($user);
                unset($unboundAttendeesByEmail[$user->getEmail()]);
            }

            foreach ($user->getEmails() as $emailEntity) {
                $email = $emailEntity->getEmail();
                if (isset($unboundAttendeesByEmail[$email])) {
                    $unboundAttendeesByEmail[$email]->setUser($user);
                    unset($unboundAttendeesByEmail[$email]);
                }
            }
        }
    }

    /**
     * @param Collection|Attendee $attendees
     *
     * @return Attendee[]
     */
    protected function getUnboundAttendeesByEmail(Collection $attendees)
    {
        $unboundAttendeesByEmail = [];
        foreach ($attendees as $attendee) {
            if ($attendee->getId() || $attendee->getUser()) {
                continue;
            }

            $unboundAttendeesByEmail[$attendee->getEmail()] = $attendee;
        }

        return $unboundAttendeesByEmail;
    }
}
