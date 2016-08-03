<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;

class CalendarEventApiTypeSubscriber implements EventSubscriberInterface
{
    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /** @var RequestStack */
    protected $requestStack;

    /**
     * CalendarEventApiTypeSubscriber constructor.
     *
     * @param CalendarEventManager $calendarEventManager
     * @param RequestStack         $requestStack
     */
    public function __construct(CalendarEventManager $calendarEventManager, RequestStack $requestStack)
    {
        $this->calendarEventManager = $calendarEventManager;
        $this->requestStack         = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT   => 'preSubmit',
            FormEvents::POST_SUBMIT  => 'postSubmitData',
        ];
    }

    /**
     * @deprecated since 1.10 'invitedUsers' field was replaced by field 'attendees'
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (empty($data)) {
            return;
        }

        if ($this->hasAttendeeInRequest()) {
            $form->remove('invitedUsers');
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        /**
         * @deprecated since 1.10 'invitedUsers' field was replaced by field 'attendees'
         */
        if ($this->hasAttendeeInRequest()) {
            $form->remove('invitedUsers');
        }

        /**
         * We check if there is no type in request data for attendee we set default value - Attendee::TYPE_REQUIRED
         */
        if (!empty($data['attendees']) && is_array($data['attendees'])) {
            $attendees = &$data['attendees'];

            foreach ($attendees as &$attendee) {
                if (!array_key_exists('type', $attendee)) {
                    $attendee['type'] = Attendee::TYPE_REQUIRED;
                }
            }

            $event->setData($data);
        }

        if (empty($data['recurrence'])) {
            $recurrence = $form->get('recurrence')->getData();
            if ($recurrence) {
                $this->calendarEventManager->removeRecurrence($recurrence);
                $form->get('recurrence')->setData(null);
            }
            unset($data['recurrence']);
            $event->setData($data);
        }
    }

    /**
     * POST_SUBMIT event handler
     *
     * @param FormEvent $event
     */
    public function postSubmitData(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var CalendarEvent $data */
        $data = $form->getData();
        if (empty($data)) {
            return;
        }

        $calendarId = $form->get('calendar')->getData();
        if (empty($calendarId)) {
            return;
        }
        $calendarAlias = $form->get('calendarAlias')->getData();
        if (empty($calendarAlias)) {
            $calendarAlias = Calendar::CALENDAR_ALIAS;
        }

        $this->calendarEventManager->setCalendar($data, $calendarAlias, (int)$calendarId);
    }

    /**
     * @deprecated since 1.10 'invitedUsers' field was replaced by field 'attendees'
     *
     * @return bool
     */
    protected function hasAttendeeInRequest()
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        return $request->request->has('attendees');
    }
}
