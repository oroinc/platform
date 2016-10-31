<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type as DbalType;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;
use Oro\Bundle\DataAuditBundle\Loggable\AuditEntityMapper;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\EntityNameProvider;

class ChangeSetToAuditFieldsConverter
{
    /** @var AuditEntityMapper */
    private $auditEntityMapper;

    /** @var AuditConfigProvider */
    private $configProvider;

    /** @var EntityNameProvider */
    private $entityNameProvider;

    /**
     * @param AuditEntityMapper   $auditEntityMapper
     * @param AuditConfigProvider $configProvider
     * @param EntityNameProvider  $entityNameProvider
     */
    public function __construct(
        AuditEntityMapper $auditEntityMapper,
        AuditConfigProvider $configProvider,
        EntityNameProvider $entityNameProvider
    ) {
        $this->auditEntityMapper = $auditEntityMapper;
        $this->configProvider = $configProvider;
        $this->entityNameProvider = $entityNameProvider;
    }

    /**
     * @param string        $auditEntryClass The class name of the audit entity
     * @param ClassMetadata $entityMetadata  The metadata of the audited entity
     * @param array         $changeSet       The changed data
     *
     * @return AbstractAuditField[]
     */
    public function convert($auditEntryClass, ClassMetadata $entityMetadata, array $changeSet)
    {
        $fields = [];
        foreach ($changeSet as $fieldName => $change) {
            if ($this->configProvider->isAuditableField($entityMetadata->name, $fieldName)) {
                $this->convertChangeSet($auditEntryClass, $entityMetadata, $fieldName, $change, $fields);
            }
        }

        return $fields;
    }

    /**
     * @param string        $auditEntryClass
     * @param ClassMetadata $entityMetadata
     * @param string        $fieldName
     * @param array         $change
     * @param array         $fields
     */
    private function convertChangeSet(
        $auditEntryClass,
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
                $auditEntryClass,
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
            $field = $this->createAuditFieldEntity($auditEntryClass, $fieldName, 'collection');
            $fields[$fieldName] = $field;
            $this->processInsertions($auditEntryClass, $new, $field);
            $this->processDeleted($auditEntryClass, $new, $field);
            $this->processChanged($auditEntryClass, $new, $field);

            $field->calculateNewValue();
        } elseif (isset($entityMetadata->associationMappings[$fieldName])) {
            $newName = $new
                ? $this->entityNameProvider->getEntityName($auditEntryClass, $new['entity_class'], $new['entity_id'])
                : null;
            $oldName = $old
                ? $this->entityNameProvider->getEntityName($auditEntryClass, $old['entity_class'], $old['entity_id'])
                : null;

            $fields[$fieldName] = $this->createAuditFieldEntity(
                $auditEntryClass,
                $fieldName,
                'text',
                $newName,
                $oldName
            );
        } else {
            $fields[$fieldName] = $this->createAuditFieldEntity(
                $auditEntryClass,
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
            $entityClass = $entity['entity_class'];
            $entityId = $entity['entity_id'];
            $field->addEntityAddedToCollection(
                $entityClass,
                $entityId,
                $this->entityNameProvider->getEntityName($auditEntryClass, $entityClass, $entityId)
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
        if ($changeSet['deleted']) {
            foreach ($changeSet['deleted'] as $entity) {
                $entityClass = $entity['entity_class'];
                $entityId = $entity['entity_id'];
                $field->addEntityRemovedFromCollection(
                    $entityClass,
                    $entityId,
                    $this->entityNameProvider->getEntityName($auditEntryClass, $entityClass, $entityId)
                );
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
                $entityClass = $entity['entity_class'];
                $entityId = $entity['entity_id'];
                $field->addEntityChangedInCollection(
                    $entityClass,
                    $entityId,
                    $this->entityNameProvider->getEntityName($auditEntryClass, $entityClass, $entityId)
                );
            }
        }
    }

    /**
     * @param string $auditEntryClass
     * @param string $field
     * @param string $dataType
     * @param mixed  $newValue
     * @param mixed  $oldValue
     *
     * @return AbstractAuditField
     */
    private function createAuditFieldEntity(
        $auditEntryClass,
        $field,
        $dataType,
        $newValue = null,
        $oldValue = null
    ) {
        $auditEntryFieldClass = $this->auditEntityMapper->getAuditEntryFieldClassForAuditEntry($auditEntryClass);

        return new $auditEntryFieldClass($field, $dataType, $newValue, $oldValue);
    }
}
