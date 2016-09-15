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

        $emailMessage = new \Swift_Message(
            $data['subject'],
            $data['body']['body'],
            $data['body']['contentType']
        );

        $emailMessage->setFrom($data['from']['email'], $data['from']['name']);
        $emailMessage->setTo($data['to']);

        $failedRecepiens = [];

        $this->mailer->send($emailMessage, $failedRecepiens);

        if(count($failedRecepiens) > 0){
            $this->logger->critical(sprintf(
                '[EmailSendingMessageProcessor] cannot sent message: "%s", receivers: %s',
                $message->getBody(),
                implode(',', $failedRecepiens)
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