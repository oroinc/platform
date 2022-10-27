<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseRelationsTopic;
use Oro\Bundle\DataAuditBundle\Exception\WrongDataAuditEntryStateException;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Processes inverse relation that should be added to data audit.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AuditChangedEntitiesInverseRelationsProcessor extends AbstractAuditProcessor implements TopicSubscriberInterface
{
    private ManagerRegistry $doctrine;

    private EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter;

    private AuditConfigProvider $auditConfigProvider;

    private EntityAuditStrategyProcessorInterface $strategyProcessor;

    public function __construct(
        ManagerRegistry $doctrine,
        EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter,
        AuditConfigProvider $auditConfigProvider,
        EntityAuditStrategyProcessorInterface $strategyProcessor
    ) {
        $this->doctrine = $doctrine;
        $this->entityChangesToAuditEntryConverter = $entityChangesToAuditEntryConverter;
        $this->auditConfigProvider = $auditConfigProvider;
        $this->strategyProcessor = $strategyProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $message->getBody();
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

            $strategy = $this->strategyProcessor->processInverseRelations($sourceEntityData);
            if (empty($strategy)) {
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
        [$old, $new] = $sourceChange;

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
        [$old, $new] = $sourceChange;

        if (isset($old['entity_id']) && $this->notEmpty($old['entity_id'])) {
            $entityId = $old['entity_id'];

            $change = $this->getChangeSetFromMap($map, $entityClass, $entityId, $fieldName);
            $change[0] = [
                'entity_class' => $sourceEntityClass,
                'entity_id' => $sourceEntityId,
                'change_set' => $this->getChangeSetFromMap($map, $sourceEntityClass, $sourceEntityId),
            ];

            $this->addChangeSetToMap($map, $entityClass, $entityId, $fieldName, $change);
        }

        if (isset($new['entity_id']) && $this->notEmpty($new['entity_id'])) {
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
     * @param ClassMetadata $sourceEntityMeta
     * @param string $sourceFieldName
     *
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
        if (empty($entityClass) || !$this->notEmpty($entityId)) {
            throw new \LogicException('Entity class either entity id cannot be empty');
        }

        if (empty($fieldName)) {
            throw new \LogicException('Field name cannot be empty');
        }

        $key = $entityClass . $entityId;
        $map[$key]['entity_class'] = $entityClass;
        $map[$key]['entity_id'] = $entityId;
        $map[$key]['change_set'][$fieldName] = $change;
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
        if (empty($entityClass) || !$this->notEmpty($entityId)) {
            throw new \LogicException('Entity class either entity id cannot be empty');
        }

        $key = $entityClass . $entityId;
        if (!$fieldName) {
            return $map[$key]['change_set'] ?? [];
        }

        if (!isset($map[$key])) {
            $map[$key] = [
                'entity_class' => $entityClass,
                'entity_id' => $entityId,
                'change_set' => [],
            ];
        }

        return $map[$key]['change_set'][$fieldName] ?? [null, null];
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
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [AuditChangedEntitiesInverseRelationsTopic::getName()];
    }

    /**
     * @param mixed $val
     * @return bool
     */
    private function notEmpty($val): bool
    {
        return $val !== null
            && $val !== false
            && $val !== []
            && $val !== '';
    }
}
