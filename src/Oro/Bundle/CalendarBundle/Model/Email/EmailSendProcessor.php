<?php

namespace Oro\Bundle\CalendarBundle\Model\Email;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

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
        if (!$calendarEvent->getParent() && count($calendarEvent->getChildEvents()) > 0) {
            foreach ($calendarEvent->getChildEvents() as $childEvent) {
                $this->addEmailNotification(
                    $childEvent,
                    [$childEvent->getCalendar()->getOwner()->getEmail()],
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
     * @param ArrayCollection $originalChildren
     * @param boolean         $notify
     *
     * @return boolean
     */
    public function sendUpdateParentEventNotification(
        CalendarEvent $calendarEvent,
        ArrayCollection $originalChildren,
        $notify = false
    ) {
        // Send notification to existing invitees if event was changed
        if (count($calendarEvent->getChildEvents()) > 0 && $notify) {
            $this->addEmailNotification(
                $calendarEvent,
                $this->getChildEmails($calendarEvent),
                self::UPDATE_INVITE_TEMPLATE_NAME
            );
            $this->process();
        }
        // Send notification to new invitees
        foreach ($calendarEvent->getChildEvents() as $childEvent) {
            if (false === $originalChildren->contains($childEvent)) {
                $this->addEmailNotification(
                    $childEvent,
                    [$childEvent->getCalendar()->getOwner()->getEmail()],
                    self::CREATE_INVITE_TEMPLATE_NAME
                );
            }
        }
        foreach ($originalChildren as $childEvent) {
            if (false === $calendarEvent->getChildEvents()->contains($childEvent)) {
                $this->addEmailNotification(
                    $calendarEvent,
                    [$childEvent->getCalendar()->getOwner()->getEmail()],
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
                case CalendarEvent::ACCEPTED:
                    $templateName = self::ACCEPTED_TEMPLATE_NAME;
                    break;
                case CalendarEvent::TENTATIVELY_ACCEPTED:
                    $templateName = self::TENTATIVE_TEMPLATE_NAME;
                    break;
                case CalendarEvent::DECLINED:
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
        } elseif (count($calendarEvent->getChildEvents()) > 0) {
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
        foreach ($parentEvent->getChildEvents() as $notifyEvent) {
            $emails[] = $notifyEvent->getCalendar()->getOwner()->getEmail();
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
        if ($childEvent->getParent()) {
            return [$childEvent->getParent()->getCalendar()->getOwner()->getEmail()];
        } else {
            return [];
        }
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
}
