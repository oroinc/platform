<?php

namespace Oro\Bundle\SearchBundle\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic;
use Oro\Bundle\SearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\SearchBundle\Transformer\MessageTransformerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Message queue processor that indexes entities by id.
 */
class IndexEntitiesByIdMessageProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var JobRunner */
    private $jobRunner;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var AbstractIndexer */
    private $indexer;

    public function __construct(
        JobRunner $jobRunner,
        DoctrineHelper $doctrineHelper,
        AbstractIndexer $indexer
    ) {
        $this->jobRunner = $jobRunner;
        $this->doctrineHelper = $doctrineHelper;
        $this->indexer = $indexer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        return $this->runUnique($message->getBody(), $message) ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [IndexEntitiesByIdTopic::getName()];
    }

    /**
     * @param array $messageBody
     *
     * @return bool
     */
    private function runUnique(array $messageBody, $message)
    {
        $closure = function () use ($messageBody) {
            /** @var array $ids */
            $ids = $messageBody[MessageTransformerInterface::MESSAGE_FIELD_ENTITY_IDS];
            $entityClass = $messageBody[MessageTransformerInterface::MESSAGE_FIELD_ENTITY_CLASS];

            $idFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);
            $repository = $this->doctrineHelper->getEntityRepository($entityClass);
            $result = $repository->findBy([$idFieldName => $ids]);

            if ($result) {
                $this->indexer->save($result);
                foreach ($result as $entity) {
                    $id = $this->doctrineHelper->getSingleEntityIdentifier($entity);
                    unset($ids[$id]);
                }
            }

            if ($ids) {
                $entities = [];
                foreach ($ids as $id) {
                    $entities[] = $this->doctrineHelper->getEntityReference($entityClass, $id);
                }
                $this->indexer->delete($entities);
            }

            return true;
        };

        return $this->jobRunner->runUniqueByMessage($message, $closure);
    }
}
