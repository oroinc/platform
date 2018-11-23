<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\DBAL\Types\Type as DbalType;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\EntityNameProvider;

/**
 * This converter is a part of EntityChangesToAuditEntryConverter and it is intended to process field changes.
 * @see \Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter
 */
class ChangeSetToAuditFieldsConverter implements ChangeSetToAuditFieldsConverterInterface
{
    /** @var AuditConfigProvider */
    private $configProvider;

    /** @var EntityNameProvider */
    private $entityNameProvider;

    /** @var AuditRecordValidator */
    private $auditRecordValidator;

    /**
     * @param AuditConfigProvider  $configProvider
     * @param EntityNameProvider   $entityNameProvider
     * @param AuditRecordValidator $auditRecordValidator
     */
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
            if ($this->configProvider->isAuditableField($entityMetadata->name, $fieldName)) {
                $this->convertChangeSet(
                    $auditEntryClass,
                    $auditFieldClass,
                    $entityMetadata,
                    $fieldName,
                    $change,
                    $fields
                );
            }
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
        list($old, $new) = $change;

        if ($entityMetadata->hasField($fieldName)) {
            $fieldMapping = $entityMetadata->getFieldMapping($fieldName);
            $fieldType = $fieldMapping['type'];
            if ($fieldType instanceof DbalType) {
                $fieldType = $fieldType->getName();
            }

            if ($old && in_array($fieldType, ['date', 'datetime'], true)) {
                $old = \DateTime::createFromFormat(DATE_ISO8601, $old);
            }

            if ($new && in_array($fieldType, ['date', 'datetime'], true)) {
                $new = \DateTime::createFromFormat(DATE_ISO8601, $new);
            }

            $fields[$fieldName] = $this->createAuditFieldEntity(
                $auditFieldClass,
                $fieldName,
                $fieldType,
                $new,
                $old
            );
        } elseif (isset($entityMetadata->associationMappings[$fieldName]) &&
            is_array($new) &&
            array_key_exists('inserted', $new) &&
            array_key_exists('deleted', $new)
        ) {
            $field = $this->createAuditFieldEntity($auditFieldClass, $fieldName, 'collection');
            $fields[$fieldName] = $field;
            $this->processInsertions($auditEntryClass, $new, $field);
            $this->processDeleted($auditEntryClass, $new, $field);
            $this->processChanged($auditEntryClass, $new, $field);

            $field->calculateNewValue();
        } elseif (isset($entityMetadata->associationMappings[$fieldName])) {
            $fields[$fieldName] = $this->createAuditFieldEntity(
                $auditFieldClass,
                $fieldName,
                'text',
                $this->getEntityName($auditEntryClass, $new),
                $this->getEntityName($auditEntryClass, $old)
            );
        } else {
            $fields[$fieldName] = $this->createAuditFieldEntity(
                $auditFieldClass,
                $fieldName,
                'text',
                (string)$new,
                (string)$old
            );
        }
    }

    /**
     * @param string             $auditEntryClass
     * @param array              $changeSet
     * @param AbstractAuditField $field
     */
    private function processInsertions($auditEntryClass, $changeSet, AbstractAuditField $field)
    {
        foreach ($changeSet['inserted'] as $entity) {
            $entityName = $this->getEntityName($auditEntryClass, $entity);
            if ($entityName) {
                $field->addEntityAddedToCollection(
                    $entity['entity_class'],
                    $entity['entity_id'],
                    $entityName
                );
            }
        }
    }

    /**
     * @param string             $auditEntryClass
     * @param array              $changeSet
     * @param AbstractAuditField $field
     */
    private function processDeleted($auditEntryClass, $changeSet, AbstractAuditField $field)
    {
        if ($changeSet['deleted']) {
            foreach ($changeSet['deleted'] as $entity) {
                $entityName = $this->getEntityName($auditEntryClass, $entity);
                if ($entityName) {
                    $field->addEntityRemovedFromCollection(
                        $entity['entity_class'],
                        $entity['entity_id'],
                        $entityName
                    );
                }
            }
        }
    }

    /**
     * @param string             $auditEntryClass
     * @param array              $changeSet
     * @param AbstractAuditField $field
     */
    private function processChanged($auditEntryClass, $changeSet, AbstractAuditField $field)
    {
        if ($changeSet['changed']) {
            foreach ($changeSet['changed'] as $entity) {
                $entityName = $this->getEntityName($auditEntryClass, $entity);
                if ($entityName) {
                    $field->addEntityChangedInCollection(
                        $entity['entity_class'],
                        $entity['entity_id'],
                        $entityName
                    );
                }
            }
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
     * @param string     $auditEntryClass
     * @param array|null $entity
     *
     * @return string|null
     */
    private function getEntityName($auditEntryClass, $entity)
    {
        if (!$entity || !$this->auditRecordValidator->validateAuditRecord($entity)) {
            return null;
        }

        return $this->entityNameProvider->getEntityName(
            $auditEntryClass,
            $entity['entity_class'],
            $entity['entity_id']
        );
    }
}
