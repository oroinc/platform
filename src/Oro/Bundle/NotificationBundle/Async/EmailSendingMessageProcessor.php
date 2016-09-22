<?php

namespace Oro\Bundle\NotificationBundle\Async;

use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class EmailSendingMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{

    /**
     * @var DirectMailer
     */
    private $mailer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DirectMailer $mailer
     * @param LoggerInterface $logger
     */
    public function __construct(DirectMailer $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        $data = array_merge([
            'fromEmail' => null,
            'fromName' => null,
            'toEmail' => null,
            'subject' => null,
            'body' => null,
            'contentType' => null
        ], $data);

        if (null == $data['fromEmail']  || null == $data['toEmail']) {
            $this->logger->critical(
                sprintf(
                    '[EmailSendingMessageProcessor] Got invalid message: "%s"',
                    $message->getBody()
                )
            );

            return self::REJECT;
        }

        $emailMessage = new \Swift_Message(
            $data['subject'],
            $data['body'],
            $data['contentType']
        );

        $emailMessage->setFrom($data['fromEmail'], $data['fromName']);
        $emailMessage->setTo($data['toEmail']);


        if (false == $this->mailer->send($emailMessage)) {
            $this->logger->critical(sprintf(
                '[EmailSendingMessageProcessor] Cannot send message: "%s"',
                $message->getBody()
            ));

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SEND_NOTIFICATION_EMAIL];
    }
}
