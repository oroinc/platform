<?php

namespace Oro\Bundle\NotificationBundle\Mailer;

use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\NotificationBundle\Entity\SpoolItem;
use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A mailer class that decorates DirectMailer for triggering an event for mass notifications.
 */
class MassEmailDirectMailer extends \Swift_Mailer
{
    /**
     * @var DirectMailer
     */
    private $directMailer;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param DirectMailer $directMailer
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        DirectMailer $directMailer,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->directMailer = $directMailer;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param array|null $failedRecipients
     * @return int
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $result = $this->directMailer->send($message, $failedRecipients);

        $spoolItem = new SpoolItem();
        $spoolItem
            ->setLogType(MassNotificationSender::NOTIFICATION_LOG_TYPE)
            ->setMessage($message);
        $this->eventDispatcher->dispatch(
            NotificationSentEvent::NAME,
            new NotificationSentEvent($spoolItem, $result)
        );

        return $result;
    }

    /**
     * @param \Swift_Events_EventListener $plugin
     */
    public function registerPlugin(\Swift_Events_EventListener $plugin)
    {
        $this->directMailer->registerPlugin($plugin);
    }

    /**
     * @return \Swift_Transport
     */
    public function getTransport()
    {
        return $this->directMailer->getTransport();
    }

    /**
     * @param string $service
     * @return object
     */
    public function createMessage($service = 'message')
    {
        return $this->directMailer->createMessage($service);
    }
}
