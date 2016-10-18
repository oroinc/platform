<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\EntityNameProvider;

class ConvertChangeSetToAuditFieldsService
{
    /** @var AuditConfigProvider */
    private $configProvider;

    /** @var EntityNameProvider */
    private $entityNameProvider;

    /**
     * @param AuditConfigProvider $configProvider
     * @param EntityNameProvider  $entityNameProvider
     */
    public function __construct(
        AuditConfigProvider $configProvider,
        EntityNameProvider $entityNameProvider
    ) {
        $this->configProvider = $configProvider;
        $this->entityNameProvider = $entityNameProvider;
    }

    /**
     * @param ClassMetadata $entityMetadata
     * @param array         $changeSet
     *
     * @return AuditField[]
     */
    public function convert(ClassMetadata $entityMetadata, array $changeSet)
    {
        $fields = [];
        foreach ($changeSet as $fieldName => $change) {
            if ($this->configProvider->isAuditableField($entityMetadata->name, $fieldName)) {
                $this->convertChangeSet($entityMetadata, $fieldName, $change, $fields);
            }
        }

        return $fields;
    }

    /**
     * @param ClassMetadata $entityMetadata
     * @param string $fieldName
     * @param array $change
     * @param array $fields
     */
    private function convertChangeSet(
        ClassMetadata $entityMetadata,
        $fieldName,
        $change,
        &$fields
    ) {
        list($old, $new) = $change;

        if ($entityMetadata->hasField($fieldName)) {
            $fieldMapping = $entityMetadata->getFieldMapping($fieldName);
            $fieldType = $fieldMapping['type'];

            if ($old && in_array($fieldType, ['date', 'datetime'], true)) {
                $old = \DateTime::createFromFormat(DATE_ISO8601, $old);
            }

            if ($new && in_array($fieldType, ['date', 'datetime'], true)) {
                $new = \DateTime::createFromFormat(DATE_ISO8601, $new);
            }

            $fields[$fieldName] = new AuditField($fieldName, $fieldType, $new, $old);
        } elseif (isset($entityMetadata->associationMappings[$fieldName]) &&
            is_array($new) &&
            array_key_exists('inserted', $new) &&
            array_key_exists('deleted', $new)
        ) {
            $fields[$fieldName] = $field = new AuditField($fieldName, 'collection', null, null);
            $this->processInsertions($new, $field);
            $this->processDeleted($new, $field);
            $this->processChanged($new, $field);

            $field->calculateNewValue();
        } elseif (isset($entityMetadata->associationMappings[$fieldName])) {
            $newName = $new ?
                $this->entityNameProvider->getEntityName($new['entity_class'], $new['entity_id']) :
                null;

            $oldName = $old ?
                $this->entityNameProvider->getEntityName($old['entity_class'], $old['entity_id']) :
                null;

            $fields[$fieldName] = new AuditField($fieldName, 'text', $newName, $oldName);
        } else {
            $fields[$fieldName] = new AuditField(
                $fieldName,
                'text',
                (string) $new,
                (string) $old
            );
        }
    }

    /**
     * @param array $changeSet
     * @param AuditField $field
     */
    private function processInsertions($changeSet, AuditField $field)
    {
        foreach ($changeSet['inserted'] as $entity) {
            $field->addEntityAddedToCollection(
                $entity['entity_class'],
                $entity['entity_id'],
                $this->entityNameProvider->getEntityName(
                    $entity['entity_class'],
                    $entity['entity_id']
                )
            );
        }
    }

    /**
     * @param array $changeSet
     * @param AuditField $field
     */
    private function processDeleted($changeSet, AuditField $field)
    {
        if ($changeSet['deleted']) {
            foreach ($changeSet['deleted'] as $entity) {
                $field->addEntityRemovedFromCollection(
                    $entity['entity_class'],
                    $entity['entity_id'],
                    $this->entityNameProvider->getEntityName(
                        $entity['entity_class'],
                        $entity['entity_id']
                    )
                );
            }
        }
    }

    /**
     * @param array $changeSet
     * @param AuditField $field
     */
    private function processChanged($changeSet, AuditField $field)
    {
        if ($changeSet['changed']) {
            foreach ($changeSet['changed'] as $entity) {
                $field->addEntityChangedInCollection(
                    $entity['entity_class'],
                    $entity['entity_id'],
                    $this->entityNameProvider->getEntityName(
                        $entity['entity_class'],
                        $entity['entity_id']
                    )
                );
            }
        }
    }
}
