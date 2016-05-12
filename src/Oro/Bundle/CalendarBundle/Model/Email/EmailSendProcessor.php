<?php

namespace Oro\Bundle\CalendarBundle\Model\Email;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor;

class EmailSendProcessor
{
    const CREATE_INVITE_TEMPLATE_NAME = 'calendar_invitation_invite';
    const UPDATE_INVITE_TEMPLATE_NAME = 'calendar_invitation_update';
    const CANCEL_INVITE_TEMPLATE_NAME = 'calendar_invitation_delete_parent_event';
    const UN_INVITE_TEMPLATE_NAME     = 'calendar_invitation_uninvite';
    const ACCEPTED_TEMPLATE_NAME      = 'calendar_invitation_accepted';
    const TENTATIVE_TEMPLATE_NAME     = 'calendar_invitation_tentative';
    const DECLINED_TEMPLATE_NAME      = 'calendar_invitation_declined';
    const REMOVE_CHILD_TEMPLATE_NAME  = 'calendar_invitation_delete_child_event';

    /**
     * @var EmailNotificationProcessor
     */
    protected $emailNotificationProcessor;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var EmailNotification[]
     */
    protected $emailNotifications;

    /**
     * @param EmailNotificationProcessor $emailNotificationProcessor
     * @param ObjectManager              $objectManager
     */
    public function __construct(
        EmailNotificationProcessor $emailNotificationProcessor,
        ObjectManager $objectManager
    ) {
        $this->emailNotificationProcessor = $emailNotificationProcessor;
        $this->em = $objectManager;
    }

    /**
     * Send invitation notification to invitees
     *
     * @param CalendarEvent $calendarEvent
     */
    public function sendInviteNotification(CalendarEvent $calendarEvent)
    {
        if (!$calendarEvent->getParent() && count($calendarEvent->getChildAttendees()) > 0) {
            foreach ($calendarEvent->getChildAttendees() as $attendee) {
                if (!$this->notifiable($attendee)) {
                    continue;
                }

                $this->addEmailNotification(
                    $attendee->getCalendarEvent(),
                    [$attendee->getEmail()],
                    self::CREATE_INVITE_TEMPLATE_NAME
                );
            }
            $this->process();
        }
    }

    /**
     * Send notification to invitees if event was changed
     *
     * @param CalendarEvent   $calendarEvent
     * @param ArrayCollection $originalAttendees
     * @param boolean         $notify
     *
     * @return boolean
     */
    public function sendUpdateParentEventNotification(
        CalendarEvent $calendarEvent,
        ArrayCollection $originalAttendees,
        $notify = false
    ) {
        $childAttendees = $calendarEvent->getChildAttendees();

        // Send notification to existing invitees if event was changed
        if (count($childAttendees) > 0 && $notify) {
            $this->addEmailNotification(
                $calendarEvent,
                $this->getChildEmails($calendarEvent),
                self::UPDATE_INVITE_TEMPLATE_NAME
            );
            $this->process();
        }
        // Send notification to new invitees
        foreach ($childAttendees as $attendee) {
            if ($this->notifiable($attendee) && false === $originalAttendees->contains($attendee)) {
                $this->addEmailNotification(
                    $attendee->getCalendarEvent(),
                    [$attendee->getEmail()],
                    self::CREATE_INVITE_TEMPLATE_NAME
                );
            }
        }
        foreach ($originalAttendees as $attendee) {
            if ($this->notifiable($attendee) && false === $childAttendees->contains($attendee)) {
                $this->addEmailNotification(
                    $attendee->getCalendarEvent(),
                    [$attendee->getEmail()],
                    self::UN_INVITE_TEMPLATE_NAME
                );
            }
        }
        if (count($this->emailNotifications) > 0) {
            $this->process();
        }

        return true;
    }

    /**
     * Send respond notification to event creator from invitees
     *
     * @param CalendarEvent $calendarEvent
     * @throws \LogicException
     */
    public function sendRespondNotification(CalendarEvent $calendarEvent)
    {
        if ($calendarEvent->getParent()) {
            switch ($calendarEvent->getInvitationStatus()) {
                case CalendarEvent::STATUS_ACCEPTED:
                    $templateName = self::ACCEPTED_TEMPLATE_NAME;
                    break;
                case CalendarEvent::STATUS_TENTATIVELY_ACCEPTED:
                    $templateName = self::TENTATIVE_TEMPLATE_NAME;
                    break;
                case CalendarEvent::STATUS_DECLINED:
                    $templateName = self::DECLINED_TEMPLATE_NAME;
                    break;
                default:
                    throw new \LogicException(
                        sprintf('Invitees try to send un-respond status %s', $calendarEvent->getInvitationStatus())
                    );
            }
            $this->addEmailNotification(
                $calendarEvent,
                $this->getParentEmail($calendarEvent),
                $templateName
            );
            $this->process();
        }
    }

    /**
     * Send notification to invitees or to event creator if event was canceled
     *
     * @param CalendarEvent $calendarEvent
     */
    public function sendDeleteEventNotification(CalendarEvent $calendarEvent)
    {
        if ($calendarEvent->getParent()) {
            $this->addEmailNotification(
                $calendarEvent,
                $this->getParentEmail($calendarEvent),
                self::REMOVE_CHILD_TEMPLATE_NAME
            );
            $this->process();
        } elseif (count($calendarEvent->getChildAttendees()) > 0) {
            $this->addEmailNotification(
                $calendarEvent,
                $this->getChildEmails($calendarEvent),
                self::CANCEL_INVITE_TEMPLATE_NAME
            );
            $this->process();
        }
    }

    public function process()
    {
        foreach ($this->emailNotifications as $notification) {
            $this->emailNotificationProcessor->process($notification->getEntity(), [$notification]);
        }
        $this->emailNotifications = [];
        $this->em->flush();
    }

    /**
     * @param CalendarEvent $parentEvent
     *
     * @return array
     */
    protected function getChildEmails(CalendarEvent $parentEvent)
    {
        $emails = [];
        /** @var CalendarEvent $notifyEvent */
        foreach ($parentEvent->getChildAttendees() as $attendee) {
            if (!$this->notifiable($attendee)) {
                continue;
            }

            $emails[] = $attendee->getEmail();
        }

        return $emails;
    }

    /**
     * @param CalendarEvent $childEvent
     *
     * @return array
     */
    protected function getParentEmail(CalendarEvent $childEvent)
    {
        $parent = $childEvent->getParent();
        if (!$parent || !$parent->getRelatedAttendee()) {
            return [];
        }

        $relatedAttendee = $parent->getRelatedAttendee();
        if (!$this->notifiable($relatedAttendee)) {
            return [];
        }

        return [$relatedAttendee->getEmail()];
    }

    /**
     * @param CalendarEvent     $calendarEvent
     * @param array             $emails
     * @param string            $templateName
     */
    protected function addEmailNotification(CalendarEvent $calendarEvent, $emails, $templateName)
    {
        $emailNotification = new EmailNotification($this->em);
        $emailNotification->setEmails($emails);
        $emailNotification->setCalendarEvent($calendarEvent);
        $emailNotification->setTemplateName($templateName);
        $this->emailNotifications[] = $emailNotification;
    }

    /**
     * @param Attendee $attendee
     *
     * @return bool
     */
    protected function notifiable(Attendee $attendee)
    {
        $origin = $attendee->getOrigin();

        return $origin && $origin->getId() === 'server';
    }
}
