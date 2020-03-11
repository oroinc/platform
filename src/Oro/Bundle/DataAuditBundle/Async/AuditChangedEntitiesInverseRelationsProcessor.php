<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Exception\WrongDataAuditEntryStateException;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Listen for flush events and send data to MQ for audition
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AuditChangedEntitiesInverseRelationsProcessor extends AbstractAuditProcessor implements TopicSubscriberInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var EntityChangesToAuditEntryConverter */
    private $entityChangesToAuditEntryConverter;

    /** @var AuditConfigProvider */
    private $auditConfigProvider;

    /**
     * @param ManagerRegistry                    $doctrine
     * @param EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter
    ) {
        $this->doctrine = $doctrine;
        $this->entityChangesToAuditEntryConverter = $entityChangesToAuditEntryConverter;
    }

    /**
     * @param AuditConfigProvider $auditConfigProvider
     */
    public function setAuditConfigProvider(AuditConfigProvider $auditConfigProvider)
    {
        $this->auditConfigProvider = $auditConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        $loggedAt = $this->getLoggedAt($body);
        $transactionId = $this->getTransactionId($body);
        $user = $this->getUserReference($body);
        $organization = $this->getOrganizationReference($body);
        $impersonation = $this->getImpersonationReference($body);
        $ownerDescription = $this->getOwnerDescription($body);

        $map = [];

        $this->processFields($body['entities_inserted'], $map);
        $this->processFields($body['entities_updated'], $map);
        $this->processFields($body['entities_deleted'], $map);

        $this->processAssociations($body['entities_inserted'], $map);
        $this->processAssociations($body['entities_updated'], $map);
        $this->processAssociations($body['entities_deleted'], $map);
        $this->processAssociations($body['collections_updated'], $map);

        $this->processEntityFromCollection($body['entities_inserted'], $map, 'inserted', 1);
        $this->processEntityFromCollection($body['entities_updated'], $map, 'changed', 1);
        $this->processEntityFromCollection($body['entities_deleted'], $map, 'deleted', 0);

        try {
            $this->entityChangesToAuditEntryConverter->convert(
                $map,
                $transactionId,
                $loggedAt,
                $user,
                $organization,
                $impersonation,
                $ownerDescription
            );
        } catch (WrongDataAuditEntryStateException $e) {
            return self::REQUEUE;
        }

        return self::ACK;
    }

    /**
     * Add fields from change sets to the map
     *
     * @param array $sourceEntitiesData
     * @param array $map
     */
    private function processFields(array $sourceEntitiesData, array &$map)
    {
        foreach ($sourceEntitiesData as $sourceEntityData) {
            $sourceEntityClass = $sourceEntityData['entity_class'];
            $sourceEntityId = $sourceEntityData['entity_id'];
            /** @var EntityManagerInterface $sourceEntityManager */
            $sourceEntityManager = $this->doctrine->getManagerForClass($sourceEntityClass);
            $sourceEntityMeta = $sourceEntityManager->getClassMetadata($sourceEntityClass);

            if (empty($sourceEntityData['change_set'])) {
                continue;
            }

            foreach ($sourceEntityData['change_set'] as $sourceFieldName => $sourceChange) {
                if (!$sourceEntityMeta->hasField($sourceFieldName) &&
                    !$sourceEntityMeta->hasAssociation($sourceFieldName)
                ) {
                    continue;
                }

                $this->addChangeSetToMap(
                    $map,
                    $sourceEntityClass,
                    $sourceEntityId,
                    $sourceFieldName,
                    $sourceChange
                );
            }
        }
    }

    /**
     * Add to one and to many associations from change sets to the map
     *
     * @param array $sourceEntitiesData
     * @param array $map
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processAssociations(array $sourceEntitiesData, array &$map)
    {
        foreach ($sourceEntitiesData as $sourceEntityData) {
            $sourceEntityClass = $sourceEntityData['entity_class'];
            $sourceEntityId = $sourceEntityData['entity_id'];
            /** @var EntityManagerInterface $sourceEntityManager */
            $sourceEntityManager = $this->doctrine->getManagerForClass($sourceEntityClass);
            $sourceEntityMeta = $sourceEntityManager->getClassMetadata($sourceEntityClass);

            if (empty($sourceEntityData['change_set'])) {
                continue;
            }

            foreach ($sourceEntityData['change_set'] as $sourceFieldName => $sourceChange) {
                if (!$sourceEntityMeta->hasAssociation($sourceFieldName)) {
                    continue;
                }

                if (!$this->auditConfigProvider->isPropagateField($sourceEntityClass, $sourceFieldName)) {
                    continue;
                }

                $fieldName = $this->getTargetFieldName($sourceEntityMeta, $sourceFieldName);
                if (!$fieldName) {
                    // the unidirectional relation
                    continue;
                }

                $entityClass = $sourceEntityMeta->associationMappings[$sourceFieldName]['targetEntity'];

                $targetEntityManager = $this->doctrine->getManagerForClass($entityClass);
                $targetEntityMeta = $targetEntityManager->getClassMetadata($entityClass);

                if ($targetEntityMeta->isSingleValuedAssociation($fieldName)) {
                    $this->processOneToAnyAssociations(
                        $sourceChange,
                        $entityClass,
                        $fieldName,
                        $sourceEntityClass,
                        $sourceEntityId,
                        $map
                    );
                } elseif ($targetEntityMeta->isCollectionValuedAssociation($fieldName)) {
                    $this->processManyToAnyAssociation(
                        $sourceChange,
                        $entityClass,
                        $fieldName,
                        $sourceEntityClass,
                        $sourceEntityId,
                        $map
                    );
                } else {
                    throw new \LogicException(
                        sprintf('Unknown field name "%s::%s"', $sourceEntityClass, $sourceFieldName)
                    );
                }
            }
        }
    }

    /**
     * Add many to * relations to the map
     *
     * @param array $sourceChange
     * @param string $entityClass
     * @param string $fieldName
     * @param string $sourceEntityClass
     * @param int $sourceEntityId
     * @param array $map
     */
    private function processManyToAnyAssociation(
        $sourceChange,
        $entityClass,
        $fieldName,
        $sourceEntityClass,
        $sourceEntityId,
        &$map
    ) {
        list($old, $new) = $sourceChange;

        $new = $this->extractValue($new, 'inserted');
        foreach ($new['inserted'] as $insert) {
            $this->processInsert($map, $insert, $entityClass, $fieldName, $sourceEntityClass, $sourceEntityId);
        }

        $old = $this->extractValue($old, 'deleted');
        foreach ($old['deleted'] as $delete) {
            $this->processDelete($map, $delete, $entityClass, $fieldName, $sourceEntityClass, $sourceEntityId);
        }
    }

    /**
     * @param mixed $value
     * @param string $key
     * @return array
     */
    private function extractValue($value, string $key): array
    {
        if (!$value) {
            return [$key => []];
        }

        if (is_array($value) && array_key_exists($key, $value)) {
            return $value;
        }

        return [$key => [$value]];
    }

    /**
     * Add inserted entities to the map
     *
     * @param array $map
     * @param array $new
     * @param string $entityClass
     * @param string $fieldName
     * @param string $sourceEntityClass
     * @param string $sourceEntityId
     */
    private function processInsert(
        array &$map,
        array $new,
        string $entityClass,
        string $fieldName,
        string $sourceEntityClass,
        string $sourceEntityId
    ) {
        $entityId = $new['entity_id'] ?? null;
        if (!$entityId) {
            return;
        }

        $change = $this->getCollectionChangeSetFromMap($map, $entityClass, $entityId, $fieldName);
        $change[1]['inserted'][$sourceEntityClass.$sourceEntityId] = [
            'entity_class' => $sourceEntityClass,
            'entity_id' => $sourceEntityId,
            'change_set' => $this->getChangeSetFromMap($map, $sourceEntityClass, $sourceEntityId),
        ];

        $this->addChangeSetToMap($map, $entityClass, $entityId, $fieldName, $change);
    }

    /**
     * Add deleted entities to the map
     *
     * @param array $map
     * @param array $old
     * @param string $entityClass
     * @param string $fieldName
     * @param string $sourceEntityClass
     * @param string $sourceEntityId
     */
    private function processDelete(
        array &$map,
        array $old,
        string $entityClass,
        string $fieldName,
        string $sourceEntityClass,
        string $sourceEntityId
    ) {
        $entityId = $old['entity_id'] ?? null;
        if (!$entityId) {
            return;
        }

        $change = $this->getCollectionChangeSetFromMap($map, $entityClass, $entityId, $fieldName);
        $change[0]['deleted'][$sourceEntityClass.$sourceEntityId] = [
            'entity_class' => $sourceEntityClass,
            'entity_id' => $sourceEntityId,
            'change_set' => $this->getChangeSetFromMap($map, $sourceEntityClass, $sourceEntityId),
        ];

        $this->addChangeSetToMap($map, $entityClass, $entityId, $fieldName, $change);
    }

    /**
     * Add one to * relations to the map
     *
     * @param array $sourceChange
     * @param string $entityClass
     * @param string $fieldName
     * @param string $sourceEntityClass
     * @param int $sourceEntityId
     * @param array $map
     */
    private function processOneToAnyAssociations(
        $sourceChange,
        $entityClass,
        $fieldName,
        $sourceEntityClass,
        $sourceEntityId,
        &$map
    ) {
        list($old, $new) = $sourceChange;

        if (!empty($old['entity_id'])) {
            $entityId = $old['entity_id'];

            $change = $this->getChangeSetFromMap($map, $entityClass, $entityId, $fieldName);
            $change[0] = [
                'entity_class' => $sourceEntityClass,
                'entity_id' => $sourceEntityId,
                'change_set' => $this->getChangeSetFromMap($map, $sourceEntityClass, $sourceEntityId),
            ];

            $this->addChangeSetToMap($map, $entityClass, $entityId, $fieldName, $change);
        }

        if (!empty($new['entity_id'])) {
            $entityId = $new['entity_id'];

            $change = $this->getChangeSetFromMap($map, $entityClass, $entityId, $fieldName);
            $change[1] = [
                'entity_class' => $sourceEntityClass,
                'entity_id' => $sourceEntityId,
                'change_set' => $this->getChangeSetFromMap($map, $sourceEntityClass, $sourceEntityId),
            ];

            $this->addChangeSetToMap($map, $entityClass, $entityId, $fieldName, $change);
        }
    }

    /**
     * Add change sets from collections elements changes to the map
     *
     * @param array $sourceEntitiesData
     * @param array $map
     * @param string $key
     * @param int $idx
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processEntityFromCollection(array $sourceEntitiesData, array &$map, string $key, int $idx)
    {
        foreach ($sourceEntitiesData as $sourceEntityData) {
            $sourceEntityClass = $sourceEntityData['entity_class'];
            $sourceEntityId = $sourceEntityData['entity_id'];
            if (empty($sourceEntityData['change_set'])) {
                continue;
            }

            /** @var EntityManagerInterface $sourceEntityManager */
            $sourceEntityManager = $this->doctrine->getManagerForClass($sourceEntityClass);
            $sourceEntityMeta = $sourceEntityManager->getClassMetadata($sourceEntityClass);
            $sourceEntity = $sourceEntityManager->find($sourceEntityClass, $sourceEntityId);
            if (!$sourceEntity) {
                // the entity may be removed after update and since we are processing stuff in background
                // it is possible that the update is processed after the real remove was performed.
                continue;
            }

            foreach ($sourceEntityMeta->associationMappings as $sourceFieldName => $associationMapping) {
                if (!empty($sourceEntityData['change_set'][$sourceFieldName])) {
                    continue;
                }

                $entityClass = $sourceEntityMeta->associationMappings[$sourceFieldName]['targetEntity'];

                $fieldName = $this->getTargetFieldName($sourceEntityMeta, $sourceFieldName);
                if (!$fieldName) {
                    // the unidirectional relation
                    continue;
                }

                $value = $sourceEntityMeta->getFieldValue($sourceEntity, $sourceFieldName);
                if (!$value) {
                    // this the case where source entity does not belong to any collections
                    continue;
                }

                $entityManager = $this->doctrine->getManagerForClass($entityClass);
                $entityIds = $this->getEntityIds($entityManager, $value);
                if (!$entityIds) {
                    continue;
                }

                foreach ($entityIds as $entityId) {
                    $change = $this->getCollectionChangeSetFromMap($map, $entityClass, $entityId, $fieldName);
                    foreach (['inserted' => 1, 'changed' => 1, 'deleted' => 0] as $changeSetType => $changeSetIdx) {
                        if ($changeSetType === $key) {
                            continue;
                        }

                        if (isset($change[$changeSetIdx][$changeSetType][$sourceEntityClass.$sourceEntityId])) {
                            $idx = $changeSetIdx;
                            $key = $changeSetType;

                            break;
                        }
                    }

                    $change[$idx][$key][$sourceEntityClass.$sourceEntityId] = [
                        'entity_class' => $sourceEntityClass,
                        'entity_id' => $sourceEntityId,
                        'change_set' => $sourceEntityData['change_set'],
                    ];

                    $this->addChangeSetToMap($map, $entityClass, $entityId, $fieldName, $change);
                }
            }
        }
    }

    /**
     * @param ClassMetadata $sourceEntityMeta
     * @param string $sourceFieldName
     * @return string
     */
    private function getTargetFieldName(ClassMetadata $sourceEntityMeta, string $sourceFieldName)
    {
        return $sourceEntityMeta->associationMappings[$sourceFieldName]['inversedBy']
            ?? $sourceEntityMeta->associationMappings[$sourceFieldName]['mappedBy']
            ?? null;
    }

    /**
     * @param array $map
     * @param string $entityClass
     * @param int $entityId
     * @param string $fieldName
     * @param array $change
     */
    private function addChangeSetToMap(array &$map, $entityClass, $entityId, $fieldName, array $change)
    {
        if (empty($entityClass) || empty($entityId)) {
            throw new \LogicException('Entity class either entity id cannot be empty');
        }

        if (empty($fieldName)) {
            throw new \LogicException('Field name cannot be empty');
        }

        $map[$entityClass.$entityId]['entity_class'] = $entityClass;
        $map[$entityClass.$entityId]['entity_id'] = $entityId;
        $map[$entityClass.$entityId]['change_set'][$fieldName] = $change;
    }

    /**
     * @param array $map
     * @param string $entityClass
     * @param int $entityId
     * @param string $fieldName
     *
     * @return array
     */
    private function getChangeSetFromMap(array &$map, $entityClass, $entityId, $fieldName = null)
    {
        if (empty($entityClass) || empty($entityId)) {
            throw new \LogicException('Entity class either entity id cannot be empty');
        }

        if (!$fieldName) {
            return $map[$entityClass.$entityId]['change_set'] ?? [];
        }

        if (false == isset($map[$entityClass.$entityId])) {
            $map[$entityClass.$entityId] = [
                'entity_class' => $entityClass,
                'entity_id' => $entityId,
                'change_set' => [],
            ];
        }

        return isset($map[$entityClass.$entityId]['change_set'][$fieldName]) ?
            $map[$entityClass.$entityId]['change_set'][$fieldName] :
            [null, null];
    }

    /**
     * @param array $map
     * @param string $entityClass
     * @param int $entityId
     * @param string $fieldName
     *
     * @return array
     */
    private function getCollectionChangeSetFromMap(array &$map, $entityClass, $entityId, $fieldName)
    {
        $change = $this->getChangeSetFromMap($map, $entityClass, $entityId, $fieldName);

        if (!isset($change[0])) {
            $change[0] = ['deleted' => []];
        }

        if (!isset($change[1])) {
            $change[1] = ['inserted' => [], 'changed' => []];
        }

        return $change;
    }

    /**
     * @param EntityManagerInterface $em
     * @param mixed $entity
     *
     * @return int[]|string[]
     */
    private function getEntityIds(EntityManagerInterface $em, $entity): array
    {
        if ($entity instanceof Collection) {
            $ids = [];
            foreach ($entity as $item) {
                $ids[] = $this->getEntityId($em, $item);
            }

            return $ids;
        }

        if (is_object($entity)) {
            return [$this->getEntityId($em, $entity)];
        }

        return [];
    }

    /**
     * @param EntityManagerInterface $em
     * @param object $entity
     * @return mixed
     */
    private function getEntityId(EntityManagerInterface $em, $entity)
    {
        return $em->getClassMetadata(ClassUtils::getClass($entity))
            ->getSingleIdReflectionProperty()
            ->getValue($entity);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::ENTITIES_INVERSED_RELATIONS_CHANGED];
    }
}
