<?php
namespace Oro\Bundle\SearchBundle\Async;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class IndexEntitiesByIdMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param MessageProducerInterface $producer
     * @param LoggerInterface          $logger
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
        $entities = JSON::decode($message->getBody());

        if (false == is_array($entities)) {
            $this->logger->error(sprintf(
                'Expected array but got: "%s"',
                is_object($entities) ? get_class($entities) : gettype($entities)
            ));

            return self::REJECT;
        }

        foreach ($entities as $entity) {
            if (empty($entity['class'])) {
                $this->logger->error(
                    'Message is invalid. Class was not found. ',
                    ['entity' => $entity]
                );

                continue;
            }

            if (empty($entity['id'])) {
                $this->logger->error(sprintf(
                    'Message is invalid. Id was not found. ',
                    ['entity' => $entity]
                ));

                continue;
            }

            $this->producer->send(Topics::INDEX_ENTITY, $entity);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::INDEX_ENTITIES];
    }
}
