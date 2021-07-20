<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierHydrator;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Resolve collections with changed entities for the audit.
 */
class AuditChangedEntitiesInverseCollectionsProcessor extends AbstractAuditProcessor implements
    TopicSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityChangesToAuditEntryConverter
     */
    private $entityChangesToAuditEntryConverter;

    /**
     * @var int
     */
    private $batchSize = 500;

    public function __construct(
        ManagerRegistry $doctrine,
        EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger
    ) {
        $this->entityChangesToAuditEntryConverter = $entityChangesToAuditEntryConverter;
        $this->doctrine = $doctrine;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
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
        $body = JSON::decode($message->getBody());
        $messageId = $message->getMessageId();
        try {
            return $this->processCollections($body, $messageId) ? self::ACK : self::REJECT;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                ['topic' => Topics::ENTITIES_INVERSED_RELATIONS_CHANGED, 'exception' => $e]
            );

            return self::REJECT;
        }
    }

    /**
     * @param array $body
     * @param string $messageId
     *
     * @return mixed|null
     */
    protected function processCollections(array $body, string $messageId)
    {
        $jobName = uniqid(sprintf('%s_', Topics::ENTITIES_INVERSED_RELATIONS_CHANGED_COLLECTIONS));
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

        return $this->jobRunner->runUnique(
            $messageId,
            $jobName,
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
                            Topics::ENTITIES_INVERSED_RELATIONS_CHANGED_COLLECTIONS_CHUNK,
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

            $sourceEntityId = $sourceEntityData['entity_id'];
            $sourceEntityClass = $sourceEntityData['entity_class'];
            $sourceEntityManager = $this->doctrine->getManagerForClass($sourceEntityClass);
            $sourceEntityMeta = $sourceEntityManager->getClassMetadata($sourceEntityClass);
            $sourceEntity = $sourceEntityManager->find($sourceEntityClass, $sourceEntityId);
            if (!$sourceEntity) {
                // the entity may be removed after update and since we are processing stuff in background
                // it is possible that the update is processed after the real remove was performed.
                continue;
            }

            $fieldsData = $this->processEntityAssociationsFromCollection(
                $sourceEntityMeta,
                $sourceEntity,
                $sourceEntityData,
            );

            if ($fieldsData) {
                $collectionsData[$sourceKey] = $sourceEntityData + ['fields' => $fieldsData, 'set' => $set];
            }
        }

        return $collectionsData;
    }

    /**
     * @param ClassMetadata $sourceEntityMeta
     * @param ExtendEntityInterface|object $sourceEntity
     * @param array $sourceEntityData
     *
     * @return array|null
     */
    private function processEntityAssociationsFromCollection(
        ClassMetadata $sourceEntityMeta,
        $sourceEntity,
        array $sourceEntityData
    ): ?array {
        $fieldsData = [];
        foreach ($sourceEntityMeta->associationMappings as $sourceFieldName => $associationMapping) {
            $targetEntityClass = $sourceEntityMeta->associationMappings[$sourceFieldName]['targetEntity'];
            $targetFieldName = $this->getTargetFieldName($sourceEntityMeta, $sourceFieldName);
            $value = $sourceEntityMeta->getFieldValue($sourceEntity, $sourceFieldName);
            $hasChangeSet = empty($sourceEntityData['change_set'][$sourceFieldName]);

            /**
             * $hasChangeSet - indicates whether there are changes.
             * $value - check the source entity does not belong to any collections.
             * $targetFieldName - check the unidirectional relation.
             */
            if (!$hasChangeSet || !$value || !$targetFieldName) {
                continue;
            }

            $entityIds = $this->getEntityIds($targetEntityClass, $value);
            if (!$entityIds) {
                continue;
            }

            $fieldsData[$sourceFieldName] = [
                'entity_class' => $targetEntityClass,
                'field_name' => $targetFieldName,
                'entity_ids' => $entityIds,
            ];
        }

        return $fieldsData;
    }

    /**
     * @param ClassMetadata $sourceEntityMeta
     * @param string $sourceFieldName
     *
     * @return string
     */
    private function getTargetFieldName(ClassMetadata $sourceEntityMeta, string $sourceFieldName): ?string
    {
        return $sourceEntityMeta->associationMappings[$sourceFieldName]['inversedBy']
            ?? $sourceEntityMeta->associationMappings[$sourceFieldName]['mappedBy']
            ?? null;
    }

    /**
     * @param string $entityClass
     * @param $entity
     *
     * @return int[]|string[]
     */
    private function getEntityIds(string $entityClass, $entity): array
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->doctrine->getManagerForClass($entityClass);
        if ($entity instanceof PersistentCollection && !$entity->isInitialized()) {
            $mapping = $entity->getMapping();
            $class = $mapping['targetEntity'];
            $field = $mapping['mappedBy'] ?? null;
            $memberOf = $mapping['type'] & ClassMetadataInfo::MANY_TO_MANY;
            if ($field) {
                return $this->getIdsWithoutHydration($entityManager, $entity, $class, $field, $memberOf);
            }
        }

        if ($entity instanceof Collection) {
            return array_map(fn ($item) => $this->getEntityId($entityManager, $item), $entity->toArray());
        }

        if (is_object($entity)) {
            return [$this->getEntityId($entityManager, $entity)];
        }

        return [];
    }

    /**
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function getIdsWithoutHydration(
        EntityManagerInterface $entityManager,
        PersistentCollection $collection,
        string $class,
        string $field,
        bool $memberOf
    ): array {
        $entityManager->getConfiguration()->addCustomHydrationMode('IdentifierHydrator', IdentifierHydrator::class);

        $fieldName = $entityManager->getClassMetadata($class)->getSingleIdentifierFieldName();
        $select = QueryBuilderUtil::sprintf('e.%s as id', $fieldName);
        $where = QueryBuilderUtil::sprintf('e.%s', $field);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $entityManager->getRepository($class)->createQueryBuilder('e');
        $queryBuilder->select($select);
        if ($memberOf) {
            $queryBuilder->where($queryBuilder->expr()->isMemberOf(':field', $where));
        } else {
            $queryBuilder->where($queryBuilder->expr()->eq($where, ':field'));
        }
        $queryBuilder->setParameter('field', $collection->getOwner());

        return $queryBuilder
            ->getQuery()
            ->getResult('IdentifierHydrator');
    }

    /**
     * @param EntityManagerInterface $em
     * @param object $entity
     *
     * @return mixed
     */
    private function getEntityId(EntityManagerInterface $em, $entity)
    {
        return $em
            ->getClassMetadata(ClassUtils::getClass($entity))
            ->getSingleIdReflectionProperty()
            ->getValue($entity);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [Topics::ENTITIES_INVERSED_RELATIONS_CHANGED];
    }
}
