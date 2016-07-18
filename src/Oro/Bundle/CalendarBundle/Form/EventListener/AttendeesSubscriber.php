<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Doctrine\Common\Collections\Collection;

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
        /** @var Attendee[]|Collection $data */
        $data      = $event->getData();
        $attendees = $event->getForm()->getData();
        if (!$attendees || !$data) {
            return;
        }

        $attendeeKeysByEmail = [];
        foreach ($attendees as $key => $attendee) {
            $id = $attendee->getEmail() ?: $attendee->getDisplayName();
            if (!$id) {
                return;
            }

            $attendeeKeysByEmail[$id] = $key;
        }

        $nextNewKey = count($attendeeKeysByEmail);
        $fixedData = [];
        foreach ($data as $attendee) {
            if (empty($attendee['email']) && empty($attendee['displayName'])) {
                return;
            }

            $id = empty($attendee['email']) ? $attendee['displayName'] : $attendee['email'];

            $key = isset($attendeeKeysByEmail[$id])
                ? $attendeeKeysByEmail[$id]
                : $nextNewKey++;

            $fixedData[$key] = $attendee;
        }

        $event->setData($fixedData);
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
