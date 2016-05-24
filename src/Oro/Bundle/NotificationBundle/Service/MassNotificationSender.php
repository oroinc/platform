<?php

namespace Oro\Bundle\NotificationBundle\Service;

use Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor;

class MassNotificationSender extends EmailNotificationProcessor
{
    
    /**
     * @param string            $subject
     * @param string            $body
     * @param string            $from
     * @param LoggerInterface   $logger
     */
    public function send($subject, $body, $from = null, $logger = null)
    {
        if (!$logger) {
            $logger = $this->logger;
        }

        $senderEmail = $from ? $from : $this->cm->get('oro_notification.email_notification_sender_email');
        $senderName  = $this->cm->get('oro_notification.email_notification_sender_name');

        foreach ($this->getRecepientEmails() as $email) {
            $message = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setFrom($senderEmail, $senderName)
                        ->setTo($email)
                        ->setBody($body);

            $this->mailer->send($message);
        }

        $this->addJob(self::SEND_COMMAND);
    }

    /**
     * @return array
     */
    protected function getRecipientEmails()
    {
        return [
            'pusachev@magecore.com'
        ];
    }

}