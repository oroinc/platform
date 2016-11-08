<?php
namespace Oro\Bundle\EmailBundle\Async;

use Psr\Log\LoggerInterface;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class UpdateEmailOwnerAssociationsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var MessageProducerInterface
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
        $data = JSON::decode($message->getBody());

        if (! isset($data['ownerClass'], $data['ownerIds']) || ! is_array($data['ownerIds'])) {
            $this->logger->critical(sprintf(
                '[UpdateEmailOwnerAssociationsMessageProcessor] Got invalid message: "%s"',
                $message->getBody()
            ));

            return self::REJECT;
        }

        foreach ($data['ownerIds'] as $id) {
            $this->producer->send(Topics::UPDATE_EMAIL_OWNER_ASSOCIATION, [
                'ownerId' => $id,
                'ownerClass' => $data['ownerClass'],
            ]);
        }

        $this->logger->info(sprintf(
            '[UpdateEmailOwnerAssociationsMessageProcessor] Sent "%s" messages',
            count($data['ownerIds'])
        ));

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS];
    }
}
