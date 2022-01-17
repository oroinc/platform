<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsChunkTopic;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Processed chunks of collection.
 */
class AuditChangedEntitiesInverseCollectionsChunkProcessor extends AbstractAuditProcessor implements
    TopicSubscriberInterface
{
    private EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter;

    private JobRunner $jobRunner;

    public function __construct(
        EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter,
        JobRunner $jobRunner
    ) {
        $this->entityChangesToAuditEntryConverter = $entityChangesToAuditEntryConverter;
        $this->jobRunner = $jobRunner;
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        return $this->runDelayed($message->getBody()) ? self::ACK : self::REJECT;
    }

    /**
     * @param array $body
     *
     * @return mixed
     */
    private function runDelayed(array $body)
    {
        return $this->jobRunner->runDelayed(
            $body['jobId'],
            function (JobRunner $jobRunner, Job $job) use ($body) {
                $map = [];
                $sourceEntityData = $body['entityData'];
                $set = $sourceEntityData['set'];
                $idx = $set === 'deleted' ? 0 : 1;

                $entityClass = $sourceEntityData['entity_class'];
                $entityId = $sourceEntityData['entity_id'];
                $entityChangeSet = $sourceEntityData['change_set'];
                $sourceKey = $entityClass . $entityId;

                foreach ($sourceEntityData['fields'] as $fieldData) {
                    $fieldName = $fieldData['field_name'];
                    foreach ($fieldData['entity_ids'] as $id) {
                        $key = $fieldData['entity_class'] . $id;
                        $map[$key] = [
                            'entity_id' => $id,
                            'entity_class' => $fieldData['entity_class'],
                        ];
                        $map[$key]['change_set'][$fieldName] = [
                            0 => ['deleted' => []],
                            1 => ['inserted' => [], 'changed' => []]
                        ];
                        $map[$key]['change_set'][$fieldName][$idx][$set][$sourceKey] = [
                            'entity_class' => $entityClass,
                            'entity_id' => $entityId,
                            'change_set' => $entityChangeSet,
                        ];
                    }
                }

                $this->convert($body, $map);

                return true;
            }
        );
    }

    private function convert(array $body, array $map): void
    {
        $this->entityChangesToAuditEntryConverter->convert(
            $map,
            $this->getTransactionId($body),
            $this->getLoggedAt($body),
            $this->getUserReference($body),
            $this->getOrganizationReference($body),
            $this->getImpersonationReference($body),
            $this->getOwnerDescription($body)
        );
    }

    public static function getSubscribedTopics(): array
    {
        return [AuditChangedEntitiesInverseCollectionsChunkTopic::getName()];
    }
}
