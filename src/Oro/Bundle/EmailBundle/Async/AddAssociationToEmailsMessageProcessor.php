<?php
namespace Oro\Bundle\EmailBundle\Async;

use Psr\Log\LoggerInterface;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class AddAssociationToEmailsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
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

        if (! isset($data['emailIds'], $data['targetClass'], $data['targetId']) || ! is_array($data['emailIds'])) {
            $this->logger->critical(sprintf(
                '[AddAssociationToEmailsMessageProcessor] Got invalid message: "%s"',
                $message->getBody()
            ));

            return self::REJECT;
        }

        foreach ($data['emailIds'] as $id) {
            $this->producer->send(Topics::ADD_ASSOCIATION_TO_EMAIL, [
                'emailId' => $id,
                'targetClass' => $data['targetClass'],
                'targetId' => $data['targetId'],
            ]);
        }

        $this->logger->info(sprintf(
            '[AddAssociationToEmailsMessageProcessor] Sent "%s" messages',
            count($data['emailIds'])
        ));

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::ADD_ASSOCIATION_TO_EMAILS];
    }
}
