<?php

namespace Oro\Bundle\SearchBundle\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntityTopic;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Message queue processor that indexes a single entity by id.
 */
class IndexEntityMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var IndexerInterface
     */
    protected $indexer;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(IndexerInterface $indexer, ManagerRegistry $doctrine, LoggerInterface $logger)
    {
        $this->indexer = $indexer;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $message->getBody();

        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrine->getManagerForClass($body['class']);
        if (null === $entityManager) {
            $this->logger->error(
                sprintf('Entity manager is not defined for class: "%s"', $body['class'])
            );

            return self::REJECT;
        }

        $repository = $entityManager->getRepository($body['class']);

        if ($entity = $repository->find($body['id'])) {
            $this->indexer->save($entity);
        } else {
            $entity = $entityManager->getReference($body['class'], $body['id']);
            $this->indexer->delete($entity);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [IndexEntityTopic::getName()];
    }
}
