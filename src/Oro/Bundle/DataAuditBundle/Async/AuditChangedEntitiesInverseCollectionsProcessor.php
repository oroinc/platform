<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsChunkTopic;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsTopic;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseRelationsTopic;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolve collections with changed entities for the audit.
 */
class AuditChangedEntitiesInverseCollectionsProcessor extends AbstractAuditProcessor implements
    TopicSubscriberInterface
{
    private int $batchSize = 500;

    public function __construct(
        private readonly MessageProducerInterface $producer,
        private readonly EntityAuditStrategyProcessorInterface $strategyProcessor,
        private readonly LoggerInterface $logger
    ) {
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        try {
            $this->processCollections($message->getBody());
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                ['topic' => AuditChangedEntitiesInverseRelationsTopic::getName(), 'exception' => $e]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    private function processCollections(array $body): void
    {
        $collectionsData = array_merge(
            $this->processEntityFromCollection($body['entities_inserted'], 'inserted'),
            $this->processEntityFromCollection($body['entities_updated'], 'changed'),
            $this->processEntityFromCollection($body['entities_deleted'], 'deleted')
        );
        if (!$collectionsData) {
            return;
        }

        unset(
            $body['entities_inserted'],
            $body['entities_updated'],
            $body['entities_deleted'],
            $body['collections_updated']
        );

        $chunks = $this->getChunks($collectionsData);
        foreach ($chunks as $chunkEntityData) {
            $chunkBody = $body;
            $chunkBody['entityData'] = $chunkEntityData;
            $this->producer->send(AuditChangedEntitiesInverseCollectionsChunkTopic::getName(), $chunkBody);
        }
    }

    private function getChunks(array $collectionsData): array
    {
        $chunks = [];
        foreach ($collectionsData as $entityData) {
            $chunks[] = $this->splitByChunks($entityData);
        }

        $chunks = array_merge(...$chunks);

        return $this->optimizeChunks($chunks);
    }

    private function optimizeChunks(array $chunks): array
    {
        $optimizedChunks = [];
        $lastOptimizedChunkEntityData = [];
        $lastOptimizedChunkEntityCount = 0;
        foreach ($chunks as $entityData) {
            $entityCount = 0;
            foreach ($entityData['fields'] as $fieldData) {
                $entityCount += \count($fieldData['entity_ids']);
            }
            if (($lastOptimizedChunkEntityCount + $entityCount) > $this->batchSize) {
                $optimizedChunks[] = $lastOptimizedChunkEntityData;
                $lastOptimizedChunkEntityData = [];
                $lastOptimizedChunkEntityCount = 0;
            }
            $lastOptimizedChunkEntityData[] = $entityData;
            $lastOptimizedChunkEntityCount += $entityCount;
        }
        if ($lastOptimizedChunkEntityData) {
            $optimizedChunks[] = $lastOptimizedChunkEntityData;
        }

        return $optimizedChunks;
    }

    private function splitByChunks(array $entityData): array
    {
        $chunks = [];
        foreach ($entityData['fields'] as $key => $fieldData) {
            $entityIds = array_chunk($fieldData['entity_ids'], $this->batchSize);
            foreach ($entityIds as $chunkEntityIds) {
                $entityData['fields'] = [
                    $key => [
                        'entity_class' => $fieldData['entity_class'],
                        'field_name' => $fieldData['field_name'],
                        'entity_ids' => $chunkEntityIds
                    ]
                ];
                $chunks[] = $entityData;
            }
        }

        return $chunks;
    }

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

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [AuditChangedEntitiesInverseCollectionsTopic::getName()];
    }
}
