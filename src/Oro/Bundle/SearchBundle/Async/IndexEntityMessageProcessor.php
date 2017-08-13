<?php
namespace Oro\Bundle\SearchBundle\Async;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IndexEntityMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var IndexerInterface
     */
    protected $indexer;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param IndexerInterface  $indexer
     * @param RegistryInterface $doctrine
     * @param LoggerInterface   $logger
     */
    public function __construct(IndexerInterface $indexer, RegistryInterface $doctrine, LoggerInterface $logger)
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
        $body = JSON::decode($message->getBody());

        if (empty($body['class'])) {
            $this->logger->error('Message is invalid. Class was not found.');

            return self::REJECT;
        }

        if (empty($body['id'])) {
            $this->logger->error('Message is invalid. Id was not found.');

            return self::REJECT;
        }

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
        return [Topics::INDEX_ENTITY];
    }
}
