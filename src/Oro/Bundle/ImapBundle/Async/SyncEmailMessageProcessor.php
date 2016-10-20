<?php
namespace Oro\Bundle\ImapBundle\Async;

use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizer;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class SyncEmailMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ImapEmailSynchronizer
     */
    private $emailSynchronizer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ImapEmailSynchronizer $emailSynchronizer
     * @param LoggerInterface $logger
     */
    public function __construct(ImapEmailSynchronizer $emailSynchronizer, LoggerInterface $logger)
    {
        $this->emailSynchronizer = $emailSynchronizer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        if (! isset($data['ids']) || ! is_array($data['ids'])) {
            $this->logger->critical(sprintf('Got invalid message. "%s"', $message->getBody()));

            return self::REJECT;
        }

        $this->emailSynchronizer->syncOrigins($data['ids']);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SYNC_EMAIL];
    }
}
