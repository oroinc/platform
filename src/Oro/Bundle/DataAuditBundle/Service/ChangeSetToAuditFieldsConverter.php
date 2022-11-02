<?php

namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\AuditFieldTypeProvider;
use Oro\Bundle\DataAuditBundle\Provider\EntityNameProvider;
use Psr\Log\LoggerAwareTrait;

/**
 * This converter is a part of EntityChangesToAuditEntryConverter and it is intended to process field changes.
 * @see \Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter
 */
class ChangeSetToAuditFieldsConverter implements ChangeSetToAuditFieldsConverterInterface
{
    use LoggerAwareTrait;

    /** @var AuditConfigProvider */
    private $configProvider;

    /** @var EntityNameProvider */
    private $entityNameProvider;

    /** @var AuditRecordValidator */
    private $auditRecordValidator;

    /** @var AuditFieldTypeProvider */
    private $auditFieldTypeProvider;

    public function __construct(
        AuditConfigProvider $configProvider,
        EntityNameProvider $entityNameProvider,
        AuditRecordValidator $auditRecordValidator
    ) {
        $this->configProvider = $configProvider;
        $this->entityNameProvider = $entityNameProvider;
        $this->auditRecordValidator = $auditRecordValidator;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(
        string $auditEntryClass,
        string $auditFieldClass,
        ClassMetadata $entityMetadata,
        array $changeSet
    ): array {
        $fields = [];
        foreach ($changeSet as $fieldName => $change) {
            $this->convertChangeSet(
                $auditEntryClass,
                $auditFieldClass,
                $entityMetadata,
                $fieldName,
                $change,
                $fields
            );
        }

        return $fields;
    }

    /**
     * @param string        $auditEntryClass
     * @param string        $auditFieldClass
     * @param ClassMetadata $entityMetadata
     * @param string        $fieldName
     * @param array         $change
     * @param array         $fields
     */
    private function convertChangeSet(
        $auditEntryClass,
        $auditFieldClass,
        ClassMetadata $entityMetadata,
        $fieldName,
        $change,
        &$fields
    ) {
        $fieldType = $this->auditFieldTypeProvider->getFieldType($entityMetadata, $fieldName);
        if (!AuditFieldTypeRegistry::hasType($fieldType)) {
            return;
        }

        if (!$this->configProvider->isAuditableField($entityMetadata->name, $fieldName)) {
            return;
        }

        list($old, $new) = $this->clearData($change);

        if ($entityMetadata->hasField($fieldName)) {
            $old = $this->processField($old, $fieldType);
            $new = $this->processField($new, $fieldType);

            $fields[$fieldName] = $this->createAuditFieldEntity(
                $auditFieldClass,
                $fieldName,
                $fieldType,
                $new,
                $old
            );
        } elseif ($this->auditFieldTypeProvider->isAssociation($entityMetadata, $fieldName)) {
            $field = $this->createAuditFieldEntity(
                $auditFieldClass,
                $fieldName,
                AuditFieldTypeRegistry::COLLECTION_TYPE
            );
            $fields[$fieldName] = $field;
            $this->processInsertions($auditEntryClass, $this->extractValue($new, 'inserted'), $field);
            $this->processChanged($auditEntryClass, $this->extractValue($new, 'changed'), $field);
            $this->processDeleted($auditEntryClass, $this->extractValue($old, 'deleted'), $field);

            $field->calculateNewValue();
        } else {
            $fields[$fieldName] = $this->createAuditFieldEntity(
                $auditFieldClass,
                $fieldName,
                AuditFieldTypeRegistry::TYPE_TEXT,
                (string)$new,
                (string)$old
            );
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
     * @param mixed $value
     * @param string $fieldType
     * @return mixed
     */
    private function processField($value, string $fieldType)
    {
        if ($value && in_array($fieldType, ['date', 'datetime', 'datetimetz', 'time'], true)) {
            return \DateTime::createFromFormat(\DateTime::ISO8601, $value);
        }
        return $value;
    }

    /**
     * @param string             $auditEntryClass
     * @param array              $changeSet
     * @param AbstractAuditField $field
     */
    private function processInsertions($auditEntryClass, $changeSet, AbstractAuditField $field)
    {
        if (!$changeSet['inserted']) {
            return;
        }

        foreach ($changeSet['inserted'] as $entity) {
            $entityName = $this->getEntityName($auditEntryClass, $entity);
            if (!$entityName) {
                continue;
            }

            $entityClass = $entity['entity_class'];
            $changeSet = array_filter(
                $entity['change_set'] ?? [],
                function ($fieldName) use ($entityClass) {
                    return $this->configProvider->isAuditableField($entityClass, $fieldName);
                },
                ARRAY_FILTER_USE_KEY
            );
            $field->addEntityAddedToCollectionWithChangeSet(
                $entity['entity_class'],
                $entity['entity_id'],
                $entityName,
                $changeSet
            );
        }
    }

    /**
     * @param string             $auditEntryClass
     * @param array              $changeSet
     * @param AbstractAuditField $field
     */
    private function processDeleted($auditEntryClass, $changeSet, AbstractAuditField $field)
    {
        if (!$changeSet['deleted']) {
            return;
        }

        foreach ($changeSet['deleted'] as $entity) {
            $entityName = $this->getEntityName($auditEntryClass, $entity);
            if (!$entityName) {
                continue;
            }
            $entityClass = $entity['entity_class'];
            $changeSet = array_filter(
                $entity['change_set'] ?? [],
                function ($fieldName) use ($entityClass) {
                    return $this->configProvider->isAuditableField($entityClass, $fieldName);
                },
                ARRAY_FILTER_USE_KEY
            );
            $field->addEntityRemovedFromCollectionWithChangeSet(
                $entity['entity_class'],
                $entity['entity_id'],
                $entityName,
                $changeSet
            );
        }
    }

    /**
     * @param string             $auditEntryClass
     * @param array              $changeSet
     * @param AbstractAuditField $field
     */
    private function processChanged($auditEntryClass, $changeSet, AbstractAuditField $field)
    {
        if (!$changeSet['changed']) {
            return;
        }

        foreach ($changeSet['changed'] as $entity) {
            $entityName = $this->getEntityName($auditEntryClass, $entity);
            if (!$entityName) {
                continue;
            }
            $entityClass = $entity['entity_class'];
            $changeSet = array_filter(
                $entity['change_set'] ?? [],
                function ($fieldName) use ($entityClass) {
                    return $this->configProvider->isAuditableField($entityClass, $fieldName);
                },
                ARRAY_FILTER_USE_KEY
            );
            $field->addEntityChangedInCollectionWithChangeSet(
                $entity['entity_class'],
                $entity['entity_id'],
                $entityName,
                $changeSet
            );
        }
    }

    /**
     * @param string $auditFieldClass
     * @param string $field
     * @param string $dataType
     * @param mixed  $newValue
     * @param mixed  $oldValue
     *
     * @return AbstractAuditField
     */
    private function createAuditFieldEntity(
        $auditFieldClass,
        $field,
        $dataType,
        $newValue = null,
        $oldValue = null
    ) {
        return new $auditFieldClass($field, $dataType, $newValue, $oldValue);
    }

    /**
     * @param string $auditEntryClass
     * @param array|null $entity
     *
     * @return string|null
     */
    private function getEntityName($auditEntryClass, $entity)
    {
        if (!$entity) {
            return null;
        }

        if (!$this->validateAuditRecord($entity)) {
            return null;
        }

        if (!empty($entity['entity_name'])) {
            return $entity['entity_name'];
        }

        return $this->entityNameProvider->getEntityName(
            $auditEntryClass,
            $entity['entity_class'],
            $entity['entity_id']
        );
    }

    /**
     * @param array $record
     *
     * @return bool
     */
    private function validateAuditRecord(array $record)
    {
        if (!array_key_exists('entity_class', $record) || !$record['entity_class']) {
            $this->logError('The "entity_class" must not be empty.', $record);

            return false;
        }

        if (!array_key_exists('entity_id', $record) || null === $record['entity_id']) {
            $this->logError('The "entity_id" must not be null.', $record);

            return false;
        }

        return true;
    }

    /**
     * @param string $message
     * @param array $record
     */
    private function logError($message, $record)
    {
        if (null === $this->logger) {
            return;
        }

        $this->logger->error($message, ['audit_record' => $record]);
    }

    public function setAuditFieldTypeProvider(AuditFieldTypeProvider $auditFieldTypeProvider)
    {
        $this->auditFieldTypeProvider = $auditFieldTypeProvider;
    }

    private function clearData(array $changes): array
    {
        if (isset($changes[1]['inserted']) || isset($changes[1]['changed'])) {
            unset($changes[1]['entity_class'], $changes[1]['entity_id'], $changes[1]['entity_name']);
        }
        if (isset($changes[0]['deleted'])) {
            unset($changes[0]['entity_class'], $changes[0]['entity_id'], $changes[0]['entity_name']);
        }

        return $changes;
    }
}
