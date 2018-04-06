<?php

namespace Oro\Bundle\SearchBundle\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\SearchBundle\Transformer\MessageTransformerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

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

    /**
     * @param JobRunner $jobRunner
     * @param DoctrineHelper $doctrineHelper
     * @param AbstractIndexer $indexer
     */
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
        $messageBody = JSON::decode($message->getBody());

        if (false === is_array($messageBody)) {
            $this->logger->error(sprintf(
                'Expected array but got: "%s"',
                is_object($messageBody) ? get_class($messageBody) : gettype($messageBody)
            ));

            return self::REJECT;
        }

        if (!$this->hasEnoughDataToBuildJobName($messageBody)) {
            $this->logger->error(sprintf(
                'Expected array with keys "class" and "context" but given: "%s"',
                implode('","', array_keys($messageBody))
            ));

            return self::REJECT;
        }

        $ownerId = $message->getMessageId();

        return $this->runUnique($messageBody, $ownerId) ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::INDEX_ENTITIES];
    }

    /**
     * @param array $messageBody
     * @param string $ownerId
     *
     * @return bool
     */
    private function runUnique(array $messageBody, $ownerId)
    {
        $jobName = $this->buildJobNameForMessage($messageBody);
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

        return $this->jobRunner->runUnique($ownerId, $jobName, $closure);
    }

    /**
     * @param array $messageBody
     *
     * @return string
     */
    private function buildJobNameForMessage(array $messageBody)
    {
        $entityClass = $messageBody[MessageTransformerInterface::MESSAGE_FIELD_ENTITY_CLASS];
        $ids = $messageBody[MessageTransformerInterface::MESSAGE_FIELD_ENTITY_IDS];
        sort($ids);
        return 'search_reindex|' . md5(serialize($entityClass) . serialize($ids));
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    private function hasEnoughDataToBuildJobName(array $data)
    {
        return !empty($data[MessageTransformerInterface::MESSAGE_FIELD_ENTITY_CLASS]) &&
            !empty($data[MessageTransformerInterface::MESSAGE_FIELD_ENTITY_IDS]);
    }
}
