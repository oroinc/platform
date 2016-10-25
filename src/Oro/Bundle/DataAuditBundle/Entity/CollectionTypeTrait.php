<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;

trait CollectionTypeTrait
{
    /**
     * @var array
     *
     * @ORM\Column(name="collection_diffs", type="json_array", nullable=true)
     */
    protected $collectionDiffs = [];

    /**
     * @param string $entityClass
     * @param int $entityId
     * @param string $entityName
     */
    public function addEntityAddedToCollection($entityClass, $entityId, $entityName)
    {
        $this->setNewValue(null);
        $this->setOldValue(null);
        $this->dataType = AuditFieldTypeRegistry::getAuditType('collection');

        $thisCollectionDiffs = $this->getCollectionDiffs();
        $thisCollectionDiffs['added'][] = [
            'entity_class' => $entityClass,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
        ];

        $this->collectionDiffs = $thisCollectionDiffs;
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     * @param string $entityName
     */
    public function addEntityRemovedFromCollection($entityClass, $entityId, $entityName)
    {
        $this->setNewValue(null);
        $this->setOldValue(null);
        $this->dataType = AuditFieldTypeRegistry::getAuditType('collection');

        $thisCollectionDiffs = $this->getCollectionDiffs();
        $thisCollectionDiffs['removed'][] = [
            'entity_class' => $entityClass,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
        ];

        $this->collectionDiffs = $thisCollectionDiffs;
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     * @param string $entityName
     */
    public function addEntityChangedInCollection($entityClass, $entityId, $entityName)
    {
        $this->setNewValue(null);
        $this->setOldValue(null);
        $this->dataType = AuditFieldTypeRegistry::getAuditType('collection');

        $thisCollectionDiffs = $this->getCollectionDiffs();
        $thisCollectionDiffs['changed'][] = [
            'entity_class' => $entityClass,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
        ];

        $this->collectionDiffs = $thisCollectionDiffs;
    }

    /**
     * @internal
     */
    public function calculateNewValue()
    {
        $thisCollectionDiffs = $this->getCollectionDiffs();

        $newValue = '';
        if ($thisCollectionDiffs['added']) {
            $newValue .= 'Added: '.implode(', ', array_map(function (array $entityData) {
                return $entityData['entity_name'];
            }, $thisCollectionDiffs['added']));
        }
        if ($thisCollectionDiffs['removed']) {
            $newValue .= "\nRemoved: ".implode(', ', array_map(function (array $entityData) {
                return $entityData['entity_name'];
            }, $thisCollectionDiffs['removed']));
        }
        if ($thisCollectionDiffs['changed']) {
            $newValue .= "\nChanged: ".implode(', ', array_map(function (array $entityData) {
                return $entityData['entity_name'];
            }, $thisCollectionDiffs['changed']));
        }
        
        $this->setNewValue($newValue);
        $this->setOldValue(null);
    }

    /**
     * @param AbstractAuditField $field
     */
    public function mergeCollectionField(AbstractAuditField $field)
    {
        $fieldCollectionDiffs = $field->getCollectionDiffs();
        $thisCollectionDiffs = $this->getCollectionDiffs();

        $thisCollectionDiffs['added'] = array_merge($thisCollectionDiffs['added'], $fieldCollectionDiffs['added']);
        $thisCollectionDiffs['removed'] = array_merge(
            $thisCollectionDiffs['removed'],
            $fieldCollectionDiffs['removed']
        );
        $thisCollectionDiffs['changed'] = array_merge(
            $thisCollectionDiffs['changed'],
            $fieldCollectionDiffs['changed']
        );

        $this->collectionDiffs = $thisCollectionDiffs;
    }

    /**
     * @return array
     */
    public function getCollectionDiffs()
    {
        $this->collectionDiffs = array_replace(
            ['added' => [], 'removed' => [], 'changed' => []],
            $this->collectionDiffs
        );

        return $this->collectionDiffs;
    }
}
