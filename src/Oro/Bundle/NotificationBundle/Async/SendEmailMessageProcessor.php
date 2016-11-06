<?php

namespace Oro\Bundle\NotificationBundle\Async;

use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class SendEmailMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var DirectMailer
     */
    private $mailer;

    /**
     * @var Processor
     */
    private $mailerProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @param DirectMailer    $mailer
     * @param Processor       $processor
     * @param LoggerInterface $logger
     */
    public function __construct(DirectMailer $mailer, Processor $processor, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->mailerProcessor = $processor;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
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

        if (null == $data['fromEmail'] || null == $data['toEmail']) {
            $this->logger->critical(
                sprintf(
                    '[SendEmailMessageProcessor] Got invalid message: "%s"',
                    $message->getBody()
                ),
                [
                    'message' => $message
                ]
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

        $this->mailerProcessor->processEmbeddedImages($emailMessage);


        //toDo: can possibly send duplicate replies. See BAP-12503

        if (! $this->mailer->send($emailMessage)) {
            $this->logger->critical(
                sprintf(
                    '[SendEmailMessageProcessor] Cannot send message: "%s"',
                    $message->getBody()
                ),
                [
                    'message' => $message
                ]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SEND_NOTIFICATION_EMAIL];
    }
}
