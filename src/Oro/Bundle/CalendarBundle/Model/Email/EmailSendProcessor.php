<?php

namespace Oro\Bundle\CalendarBundle\Model\Email;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Email\EmailNotification;

class EmailSendProcessor
{
    // @TODO: Add name of templates
    const CREATE_INVITE_TEMPLATE_NAME = 'calendar_invitation_invite';
    const UPDATE_INVITE_TEMPLATE_NAME = 'calendar_invitation_invite';
    const CANCEL_INVITE_TEMPLATE_NAME = 'calendar_invitation_invite';
    const UN_INVITE_TEMPLATE_NAME     = 'calendar_invitation_invite';
    const ACCEPTED_TEMPLATE_NAME      = 'calendar_invitation_invite';
    const TENTATIVE_TEMPLATE_NAME     = 'calendar_invitation_invite';
    const DECLINED_TEMPLATE_NAME      = 'calendar_invitation_invite';
    const REMOVE_CHILD_TEMPLATE_NAME  = 'calendar_invitation_invite';

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
            $this->addEmailNotification(
                $calendarEvent,
                $this->getChildEmails($calendarEvent),
                self::CREATE_INVITE_TEMPLATE_NAME
            );
            $this->sendNotificationEmail($calendarEvent);
        }
    }

    /**
     * Send notification to invitees if event was changed
     *
     * @param CalendarEvent   $calendarEvent
     * @param CalendarEvent   $dirtyEvent
     * @param ArrayCollection $originalChildren
     */
    public function sendUpdateParentEventNotification(
        CalendarEvent $calendarEvent,
        CalendarEvent $dirtyEvent,
        ArrayCollection $originalChildren
    ) {
        // Send notification to existing invitees if event was changed time
        if (count($calendarEvent->getChildEvents()) > 0 && (
            $calendarEvent->getStart() != $dirtyEvent->getStart() ||
            $calendarEvent->getEnd() != $dirtyEvent->getEnd()
        )) {
            $this->addEmailNotification(
                $calendarEvent,
                $this->getChildEmails($calendarEvent),
                self::UPDATE_INVITE_TEMPLATE_NAME
            );
            $this->sendNotificationEmail($calendarEvent);
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
                    $childEvent,
                    [$childEvent->getCalendar()->getOwner()->getEmail()],
                    self::UN_INVITE_TEMPLATE_NAME
                );
            }
        }
        if (count($this->emailNotifications) > 0) {
            $this->sendNotificationEmail($calendarEvent);
        }
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
            $this->sendNotificationEmail($calendarEvent);
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
            $this->sendNotificationEmail($calendarEvent);
        } else if (count($calendarEvent->getChildEvents()) > 0) {
            $this->addEmailNotification(
                $calendarEvent,
                $this->getChildEmails($calendarEvent),
                self::CANCEL_INVITE_TEMPLATE_NAME
            );
            $this->sendNotificationEmail($calendarEvent);
        }
    }

    /**
     * Send notification email
     *
     * @param CalendarEvent              $calendarEvent
     */
    public function sendNotificationEmail(CalendarEvent $calendarEvent)
    {
        try {
            $this->emailNotificationProcessor->process(
                $calendarEvent,
                $this->emailNotifications
            );
        } catch (\Exception $exception) {
        }
        $this->emailNotifications = [];
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
            return (array)$childEvent->getParent()->getCalendar()->getOwner()->getEmail();
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
