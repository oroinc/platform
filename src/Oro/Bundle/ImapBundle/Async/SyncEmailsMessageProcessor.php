<?php
namespace Oro\Bundle\ImapBundle\Async;

use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizer;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class SyncEmailsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ImapEmailSynchronizer
     */
    private $producer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     */
    public function __construct(MessageProducerInterface $producer, LoggerInterface $logger)
    {
        $this->producer = $producer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (! isset($body['ids']) || ! is_array($body['ids'])) {
            $this->logger->critical('Got invalid message');

            return self::REJECT;
        }

        foreach ($body['ids'] as $id) {
            $this->producer->send(Topics::SYNC_EMAIL, ['id' => $id]);
        }


        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SYNC_EMAILS];
    }
}
