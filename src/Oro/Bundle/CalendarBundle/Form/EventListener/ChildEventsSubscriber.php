<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\PhpUtils\ArrayUtil;

class ChildEventsSubscriber implements EventSubscriberInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var CalendarEvent */
    protected $parentEvent;

    /**
     * @param FormBuilderInterface $builder
     * @param ManagerRegistry $registry
     * @param SecurityFacade $securityFacade
     * @param string $childEventsFieldName
     */
    public function __construct(
        FormBuilderInterface $builder,
        ManagerRegistry $registry,
        SecurityFacade $securityFacade,
        $childEventsFieldName = 'attendees'
    ) {
        $this->registry= $registry;
        $this->securityFacade = $securityFacade;

        // get existing events
        $builder->get($childEventsFieldName)
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmitChildEvents']);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit', // extract master event
            FormEvents::POST_SUBMIT => 'postSubmit', // synchronize child events
        ];
    }

    /**
     * Stores original parentEvent for later use
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getForm()->getData();
        if ($data) {
            $this->parentEvent = $data;
        }
    }

    /**
     * Replaces newly created attendees by transformer with existing attendees
     * to preserve all attributes of attendees (origin, invitation status).
     *
     * @param FormEvent $event
     */
    public function postSubmitChildEvents(FormEvent $event)
    {
        /** @var Attendee[] $attendees */
        $attendees = $event->getForm()->getData();
        if ($attendees && $this->parentEvent) {
            $existingAttendees = $this->parentEvent->getAttendees();
            foreach ($attendees as $key => $attendee) {
                $existingAttendee = ArrayUtil::find(
                    function (Attendee $existingAttendee) use ($attendee) {
                        if ($attendee->getUser()) {
                            return $existingAttendee->getUser() &&
                                $existingAttendee->getUser()->getId() === $attendee->getUser()->getId();
                        }

                        return !$existingAttendee->getUser() && $existingAttendee->getEmail() === $attendee->getEmail();
                    },
                    $existingAttendees->toArray()
                );

                if (!$existingAttendee) {
                    continue;
                }

                $attendees[$key] = $existingAttendee;
            }
        }
    }

    /**
     * - creates/removes calendar events based on attendee changes
     * - makes sure displayName is not empty
     * - sets default attendee status
     * - updates duplicated values of child events
     * (It would be better to have separate entity for common data. Could be e.g. CalendarEventInfo)
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var CalendarEvent $parentEvent */
        $parentEvent = $event->getForm()->getData();
        $this->updateCalendarEvents($parentEvent);
        $this->updateAttendeeDisplayNames($parentEvent);
        if (!$parentEvent) {
            return;
        }

        $this->setDefaultAttendeeStatus($parentEvent->getRelatedAttendee(), CalendarEvent::STATUS_ACCEPTED);
        $this->setDefaultAttendeeType($parentEvent->getRelatedAttendee());
        foreach ($parentEvent->getChildEvents() as $calendarEvent) {
            $calendarEvent
                ->setTitle($parentEvent->getTitle())
                ->setDescription($parentEvent->getDescription())
                ->setStart($parentEvent->getStart())
                ->setEnd($parentEvent->getEnd())
                ->setAllDay($parentEvent->getAllDay());
        }

        foreach ($parentEvent->getChildAttendees() as $attendee) {
            $this->setDefaultAttendeeStatus($attendee);
            $this->setDefaultAttendeeType($attendee);
        }
    }

    /**
     * Creates/removes calendar events based on attendee changes
     *
     * @param CalendarEvent $parent
     */
    protected function updateCalendarEvents(CalendarEvent $parent)
    {
        $attendeesByUserId = [];
        $attendees = $parent->getAttendees();
        foreach ($attendees as $attendee) {
            if (!$attendee->getUser()) {
                continue;
            }

            $attendeesByUserId[$attendee->getUser()->getId()] = $attendee;
        }
        $currentUserIds = array_keys($attendeesByUserId);

        $calendarEventOwnerIds = [];
        $calendar = $parent->getCalendar();
        if ($calendar && $calendar->getOwner()) {
            $owner = $calendar->getOwner();
            if (isset($attendeesByUserId[$owner->getId()])) {
                $parent->setRelatedAttendee($attendeesByUserId[$owner->getId()]);
            }
            $calendarEventOwnerIds[] = $calendar->getOwner()->getId();
        }
        $events = $parent->getChildEvents();
        foreach ($events as $event) {
            $calendar = $event->getCalendar();
            if (!$calendar) {
                continue;
            }

            $owner = $calendar->getOwner();
            if (!$owner) {
                continue;
            }

            $ownerId = $owner->getId();
            if (!in_array($ownerId, $currentUserIds)) {
                $parent->removeChildEvent($event);

                continue;
            }

            $calendarEventOwnerIds[] = $ownerId;
        }

        $this->createChildEvent(
            $parent,
            array_diff($currentUserIds, $calendarEventOwnerIds),
            $attendeesByUserId
        );
    }

    /**
     * Makes sure displayName is not empty
     *
     * @param CalendarEvent $parent
     */
    protected function updateAttendeeDisplayNames(CalendarEvent $parent)
    {
        foreach ($parent->getAttendees() as $attendee) {
            if ($attendee->getDisplayName()) {
                continue;
            }

            $displayName = $attendee->getUser()
                ? $attendee->getUser()->getFullName()
                : $attendee->getEmail();
            $attendee->setDisplayName($displayName);
        }
    }

    /**
     * @param Attendee|null $attendee
     * @param string        $status
     */
    protected function setDefaultAttendeeStatus(Attendee $attendee = null, $status = CalendarEvent::STATUS_NONE)
    {
        if (!$attendee || $attendee->getStatus()) {
            return;
        }

        $statusEnum = $this->registry
            ->getRepository(ExtendHelper::buildEnumValueClassName(Attendee::STATUS_ENUM_CODE))
            ->find($status);
        $attendee->setStatus($statusEnum);
    }

    /**
     * @param Attendee|null $attendee
     * @param string $type
     */
    protected function setDefaultAttendeeType(Attendee $attendee = null, $type = Attendee::TYPE_OPTIONAL)
    {
        if (!$attendee || $attendee->getType()) {
            return;
        }

        $repo = $this->registry
            ->getRepository(ExtendHelper::buildEnumValueClassName(Attendee::TYPE_ENUM_CODE));


        $typeEnum = $this->registry
            ->getRepository(ExtendHelper::buildEnumValueClassName(Attendee::TYPE_ENUM_CODE))
            ->find($type);
        $attendee->setType($typeEnum);
    }

    /**
     * @param CalendarEvent $parent
     * @param array         $missingEventUserIds
     * @param array         $attendeesByUserId
     */
    protected function createChildEvent(CalendarEvent $parent, array $missingEventUserIds, array $attendeesByUserId)
    {
        if ($missingEventUserIds) {
            /** @var CalendarRepository $calendarRepository */
            $calendarRepository = $this->registry->getRepository('OroCalendarBundle:Calendar');
            $organizationId     = $this->securityFacade->getOrganizationId();

            $calendars = $calendarRepository->findDefaultCalendars($missingEventUserIds, $organizationId);
            foreach ($calendars as $calendar) {
                $event = new CalendarEvent();
                $event->setCalendar($calendar);
                $parent->addChildEvent($event);
                if ($calendar->getOwner() && isset($attendeesByUserId[$calendar->getOwner()->getId()])) {
                    $event->setRelatedAttendee($attendeesByUserId[$calendar->getOwner()->getId()]);
                }
            }
        }
    }
}
