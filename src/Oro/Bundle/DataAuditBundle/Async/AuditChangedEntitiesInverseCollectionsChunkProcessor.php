<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsChunkTopic;
use Oro\Bundle\DataAuditBundle\Exception\WrongDataAuditEntryStateException;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Processed chunks of collection.
 */
class AuditChangedEntitiesInverseCollectionsChunkProcessor extends AbstractAuditProcessor implements
    TopicSubscriberInterface
{
    use LoggerAwareTrait;

    private EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter;
    private JobRunner $jobRunner;
    private AuditConfigProvider $auditConfigProvider;
    private MessageProducerInterface $producer;

    public function __construct(
        EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter,
        JobRunner $jobRunner,
        AuditConfigProvider $auditConfigProvider
    ) {
        $this->entityChangesToAuditEntryConverter = $entityChangesToAuditEntryConverter;
        $this->jobRunner = $jobRunner;
        $this->auditConfigProvider = $auditConfigProvider;
    }

    public function setProducer(MessageProducerInterface $producer): void
    {
        $this->producer = $producer;
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();
        $changes = $this->getChanges($body);
        foreach ($changes as $i => $entityChanges) {
            try {
                $this->convert($body, $entityChanges);
            } catch (\Throwable $e) {
                return $this->processConvertationException($body, $i, $e);
            }
        }

        return self::ACK;
    }

    private function getChanges(array $body): array
    {
        $entityData = $body['entityData'];
        if (isset($entityData['entity_class'])) {
            return [$this->getEntityChanges($entityData)];
        }

        $changes = [];
        foreach ($entityData as $item) {
            $changes[] = $this->getEntityChanges($item);
        }

        return $changes;
    }

    private function getEntityChanges(array $sourceEntityData): array
    {
        $map = [];
        $set = $sourceEntityData['set'];
        $idx = $set === 'deleted' ? 0 : 1;

        $entityClass = $sourceEntityData['entity_class'];
        $entityId = $sourceEntityData['entity_id'];
        $entityChangeSet = $sourceEntityData['change_set'];
        $sourceKey = $entityClass . $entityId;

        foreach ($sourceEntityData['fields'] as $relatedField => $fieldData) {
            if (
                !is_a($entityClass, AbstractLocalizedFallbackValue::class, true)
                && !$this->auditConfigProvider->isPropagateField($entityClass, $relatedField)
            ) {
                // if its dynamic relationship between LocalizedFallbackValue, keep ordinary behavior
                continue;
            }

            $fieldName = $fieldData['field_name'];
            foreach ($fieldData['entity_ids'] as $id) {
                $key = $fieldData['entity_class'] . $id;
                $map[$key] = [
                    'entity_class' => $fieldData['entity_class'],
                    'entity_id' => $id
                ];
                $map[$key]['change_set'][$fieldName] = [
                    0 => ['deleted' => []],
                    1 => ['inserted' => [], 'changed' => []]
                ];
                $map[$key]['change_set'][$fieldName][$idx][$set][$sourceKey] = [
                    'entity_class' => $entityClass,
                    'entity_id' => $entityId,
                    'change_set' => $entityChangeSet
                ];
            }
        }

        return $map;
    }

    private function convert(array $body, array $entityChanges): void
    {
        if (!$entityChanges) {
            return;
        }

        $this->entityChangesToAuditEntryConverter->convert(
            $entityChanges,
            $this->getTransactionId($body),
            $this->getLoggedAt($body),
            $this->getUserReference($body),
            $this->getOrganizationReference($body),
            $this->getImpersonationReference($body),
            $this->getOwnerDescription($body)
        );
    }

    private function processConvertationException(array $body, int $failedEntityIndex, \Throwable $exception): string
    {
        $processResult = self::ACK;
        $entityData = $body['entityData'];
        $entityCount = !isset($entityData['entity_class'])
            ? \count($entityData)
            : 1;
        $logContext = ['topic' => AuditChangedEntitiesInverseCollectionsChunkTopic::getName()];
        if (!isset($entityData['entity_class'])) {
            $logContext['entityIndex'] = $failedEntityIndex;
        }
        $logContext['exception'] = $exception;
        if ($exception instanceof WrongDataAuditEntryStateException) {
            $this->logger->warning(
                'Unexpected retryable database exception occurred during Audit Changed Entities build.',
                $logContext
            );
            if (1 === $entityCount) {
                $processResult = self::REQUEUE;
            } else {
                // attempt to process the failed entity independently
                $this->requeueEntities($body, [$entityData[$failedEntityIndex]]);
                // schedule processing for unprocessed entities
                if (($entityCount - $failedEntityIndex - 1) > 0) {
                    $this->requeueEntities($body, \array_slice($entityData, $failedEntityIndex + 1));
                }
            }
        } else {
            $this->logger->error(
                'Unexpected exception occurred during Audit Changed Entities build.',
                $logContext
            );
            if (1 === $entityCount) {
                $processResult = self::REJECT;
            } elseif (($entityCount - $failedEntityIndex - 1) > 0) {
                // schedule processing for unprocessed entities
                $this->requeueEntities($body, \array_slice($entityData, $failedEntityIndex + 1));
            }
        }

        return $processResult;
    }

    private function requeueEntities(array $body, array $entityData): void
    {
        $requeueBody = $body;
        $requeueBody['entityData'] = $entityData;
        $this->producer->send(AuditChangedEntitiesInverseCollectionsChunkTopic::getName(), $requeueBody);
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [AuditChangedEntitiesInverseCollectionsChunkTopic::getName()];
    }
}
