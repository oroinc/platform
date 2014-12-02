<?php

namespace Oro\Bundle\CalendarBundle\Model\Email;

use Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class EmailSendProcessor
{
    // @TODO: Add name of templates
    const CREATE_INVITE_TEMPLATE_NAME = '';
    const UPDATE_INVITE_TEMPLATE_NAME = '';
    const CANCEL_INVITE_TEMPLATE_NAME = '';
    const UPDATE_STATUS_TEMPLATE_NAME = '';
    const DECLINE_TEMPLATE_NAME       = '';

    /**
     * @var EmailNotificationProcessor
     */
    protected $emailNotificationProcessor;

    /**
     * @var EmailNotification
     */
    protected $emailNotification;

    /**
     * @param EmailNotificationProcessor $emailNotificationProcessor
     * @param EmailNotification          $emailNotification
     */
    public function __construct(
        EmailNotificationProcessor $emailNotificationProcessor,
        EmailNotification $emailNotification
    ) {
        $this->emailNotificationProcessor = $emailNotificationProcessor;
        $this->emailNotification = $emailNotification;
    }

    /**
     * Send invitation notification to invitees
     *
     * @param CalendarEvent $calendarEvent
     */
    public function sendInviteNotification(CalendarEvent $calendarEvent)
    {
        if (!$calendarEvent->getParent() && count($calendarEvent->getChildEvents()) > 0) {
            $this->emailNotification->setEmails($this->getChildEmails($calendarEvent));
            $this->sendNotificationEmail($calendarEvent, self::CREATE_INVITE_TEMPLATE_NAME);
        }
    }

    /**
     * Send notification to invitees or to event creator if event was updated
     *
     * @param CalendarEvent $calendarEvent
     * @param CalendarEvent $dirtyEvent
     */
    public function sendUpdateEventNotification(CalendarEvent $calendarEvent, CalendarEvent $dirtyEvent)
    {
        if ($calendarEvent->getParent()) {
            $this->emailNotification->setEmails($this->getParentEmail($calendarEvent));
            $this->sendNotificationEmail($calendarEvent, self::UPDATE_STATUS_TEMPLATE_NAME);
        } else {
            if (count($calendarEvent->getChildEvents()) > 0 && (
                $calendarEvent->getStart() != $dirtyEvent->getStart() ||
                $calendarEvent->getEnd() != $dirtyEvent->getEnd()
            )) {
                $this->emailNotification->setEmails($this->getChildEmails($calendarEvent));
                $this->sendNotificationEmail($calendarEvent, self::UPDATE_INVITE_TEMPLATE_NAME);
            }
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
            $this->emailNotification->setEmails($this->getParentEmail($calendarEvent));
            $this->sendNotificationEmail($calendarEvent, self::DECLINE_TEMPLATE_NAME);
        } else if (count($calendarEvent->getChildEvents()) > 0) {
            $this->emailNotification->setEmails($this->getChildEmails($calendarEvent));
            $this->sendNotificationEmail($calendarEvent, self::CANCEL_INVITE_TEMPLATE_NAME);
        }
    }

    /**
     * Send notification email
     *
     * @param CalendarEvent              $calendarEvent
     * @param string                     $templateName
     */
    public function sendNotificationEmail(CalendarEvent $calendarEvent, $templateName)
    {
        $this->emailNotification->setCalendarEvent($calendarEvent);
        $this->emailNotification->setTemplateName($templateName);

        try {
            $this->emailNotificationProcessor->process(
                $calendarEvent,
                [$this->emailNotification]
            );
        } catch (\Exception $exception) {
        }
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
            return (array)$childEvent->getParent()->getOwner()->getEmail();
        } else {
            return [];
        }
    }
}
