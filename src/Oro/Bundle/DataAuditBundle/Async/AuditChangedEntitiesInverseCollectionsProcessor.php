<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsChunkTopic;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsTopic;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseRelationsTopic;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolve collections with changed entities for the audit.
 */
class AuditChangedEntitiesInverseCollectionsProcessor extends AbstractAuditProcessor implements
    TopicSubscriberInterface
{
    private JobRunner $jobRunner;

    private MessageProducerInterface $producer;

    private LoggerInterface $logger;

    private EntityAuditStrategyProcessorInterface $strategyProcessor;

    private int $batchSize = 500;

    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        EntityAuditStrategyProcessorInterface $strategyProcessor
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
        $this->strategyProcessor = $strategyProcessor;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        try {
            return $this->processCollections($message->getBody(), $message) ? self::ACK : self::REJECT;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                ['topic' => AuditChangedEntitiesInverseRelationsTopic::getName(), 'exception' => $e]
            );

            return self::REJECT;
        }
    }

    /**
     * @param array $body
     * @param MessageInterface $message
     *
     * @return mixed|null
     */
    protected function processCollections(array $body, MessageInterface $message)
    {
        $collectionsData = array_merge(
            $this->processEntityFromCollection($body['entities_inserted'], 'inserted'),
            $this->processEntityFromCollection($body['entities_updated'], 'changed'),
            $this->processEntityFromCollection($body['entities_deleted'], 'deleted')
        );

        unset(
            $body['entities_inserted'],
            $body['entities_updated'],
            $body['entities_deleted'],
            $body['collections_updated']
        );

        return $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner, Job $job) use ($body, $collectionsData) {
                $index = 0;
                foreach ($collectionsData as $sourceEntityData) {
                    $this->createDelayed($jobRunner, $job, $sourceEntityData, $body, $index);
                }

                return true;
            }
        );
    }

    private function createDelayed(JobRunner $jobRunner, Job $job, array $entityData, array $body, int &$index): void
    {
        if (!\is_array($entityData) || !isset($entityData['fields'])) {
            return;
        }

        foreach ($entityData['fields'] as $key => $fieldData) {
            $entityIds = array_chunk($fieldData['entity_ids'], $this->getBatchSize());
            foreach ($entityIds as $chunk) {
                $entityData['fields'] = [
                    $key => [
                        'entity_class' => $fieldData['entity_class'],
                        'field_name' => $fieldData['field_name'],
                        'entity_ids' => $chunk
                    ]
                ];

                $jobRunner->createDelayed(
                    sprintf('%s:chunk:%s', $job->getName(), ++$index),
                    function (JobRunner $jobRunner, Job $child) use ($body, $entityData) {
                        $body['entityData'] = $entityData;
                        $body['jobId'] = $child->getId();
                        $this->producer->send(
                            AuditChangedEntitiesInverseCollectionsChunkTopic::getName(),
                            new Message($body)
                        );
                    }
                );
            }
        }
    }

    /**
     * Prepare data from collections.
     */
    private function processEntityFromCollection(array $sourceEntitiesData, string $set): array
    {
        $collectionsData = [];
        foreach ($sourceEntitiesData as $sourceKey => $sourceEntityData) {
            if (empty($sourceEntityData['change_set'])) {
                continue;
            }

            $fieldsData = $this->strategyProcessor->processInverseCollections($sourceEntityData);
            if ($fieldsData) {
                $collectionsData[$sourceKey] = $sourceEntityData + ['fields' => $fieldsData, 'set' => $set];
            }
        }

        return $collectionsData;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [AuditChangedEntitiesInverseCollectionsTopic::getName()];
    }
}
