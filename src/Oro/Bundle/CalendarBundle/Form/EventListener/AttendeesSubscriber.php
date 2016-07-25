<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;

class AttendeesSubscriber implements EventSubscriberInterface
{
    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /**
     * @param AttendeeRelationManager $attendeeRelationManager
     */
    public function __construct(AttendeeRelationManager $attendeeRelationManager)
    {
        $this->attendeeRelationManager = $attendeeRelationManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT  => ['fixSubmittedData', 100],
            FormEvents::POST_SUBMIT => ['postSubmit', -100],
        ];
    }

    /**
     * Makes sure indexes of attendees from request are equal to indexes of the same
     * attendees so that in the end we end up with correct data.
     *
     * @param FormEvent $event
     */
    public function fixSubmittedData(FormEvent $event)
    {
        /**
         * Fix case when submitted data is empty string
         */
        $data = $event->getData();
        if (empty($data)) {
            $event->setData([]);

            return;
        }

        /** @var Attendee[] $attendees */
        $attendees = $event->getForm()->getData();
        if (!$attendees) {
            return;
        }

        $attendeeKeysByEmail = [];
        foreach ($attendees as $key => $attendee) {
            if ($attendee->getEmail()) {
                $attendeeKeysByEmail[$attendee->getEmail()] = $key;
            } elseif ($attendee->getDisplayName()) {
                $attendeeKeysByEmail[$attendee->getDisplayName()] = $key;
            } else {
                return;
            }
        }

        $nextNewKey = count($attendeeKeysByEmail);
        $attendeesWithFixedKeys = [];
        foreach ($data as $attendee) {
            $id = null;
            if (!empty($attendee['email'])) {
                $id = $attendee['email'];
            } elseif (!empty($attendee['displayName'])) {
                $id = $attendee['displayName'];
            }

            if ($id && isset($attendeeKeysByEmail[$id])) {
                $attendeesWithFixedKeys[$attendeeKeysByEmail[$id]] = $attendee;
            } else {
                $attendeesWithFixedKeys[$nextNewKey++] = $attendee;
            }
        }

        $event->setData($attendeesWithFixedKeys);
    }

    /**
     * Tries to search and bind users to attendees by email
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $attendees = $event->getData();
        if (!$attendees) {
            return;
        }

        $this->attendeeRelationManager->bindAttendees($attendees);
    }
}
