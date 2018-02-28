<?php
namespace Oro\Bundle\ImapBundle\Async;

use Oro\Bundle\EmailBundle\Sync\EmailSynchronizerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class SyncEmailMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var EmailSynchronizerInterface
     */
    private $emailSynchronizer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param EmailSynchronizerInterface $emailSynchronizer
     * @param LoggerInterface $logger
     */
    public function __construct(EmailSynchronizerInterface $emailSynchronizer, LoggerInterface $logger)
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

        if (! isset($data['id'])) {
            $this->logger->critical('Got invalid message');

            return self::REJECT;
        }

        $this->emailSynchronizer->syncOrigins([$data['id']]);

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
