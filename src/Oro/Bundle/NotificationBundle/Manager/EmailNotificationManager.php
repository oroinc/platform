<?php

namespace Oro\Bundle\NotificationBundle\Manager;

use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\NotificationBundle\Model\EmailNotificationInterface;
use Psr\Log\LoggerInterface;

class EmailNotificationManager
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EmailRenderer
     */
    private $renderer;

    /**
     * @var EmailNotificationSender
     */
    private $emailNotificationSender;

    /**
     * EmailNotificationManager constructor.
     *
     * @param EmailRenderer $emailRenderer
     * @param EmailNotificationSender $emailNotificationSender
     * @param LoggerInterface $logger
     */
    public function __construct(
        EmailRenderer $emailRenderer,
        EmailNotificationSender $emailNotificationSender,
        LoggerInterface $logger
    ) {
        $this->renderer = $emailRenderer;
        $this->emailNotificationSender = $emailNotificationSender;
        $this->logger = $logger;
    }

    /**
     * Sends the email notifications
     *
     * @param mixed                        $object
     * @param EmailNotificationInterface[] $notifications
     * @param LoggerInterface              $logger Override for default logger. If this parameter is specified
     *                                             this logger will be used instead of a logger specified
     *                                             in the constructor
     * @param array                        $params Additional params for template renderer
     */
    public function process($object, $notifications, LoggerInterface $logger = null, $params = [])
    {
        if (null == $logger) {
            $logger = $this->logger;
        }

        foreach ($notifications as $notification) {
            $emailTemplate = $notification->getTemplate();
            try {
                list($subject, $body) = $this->renderer->compileMessage(
                    $emailTemplate,
                    ['entity' => $object] + $params
                );
                $contentType = 'txt' == $emailTemplate->getType() ? 'text/plain' : 'text/html';

                $this->emailNotificationSender->send($notification, $subject, $body, $contentType);
            } catch (\Twig_Error $e) {
                $identity = method_exists($emailTemplate, '__toString')
                    ? (string)$emailTemplate : $emailTemplate->getSubject();

                $logger->error(
                    sprintf('Rendering of email template "%s" failed. %s', $identity, $e->getMessage()),
                    ['exception' => $e]
                );
            }
        }
    }
}
