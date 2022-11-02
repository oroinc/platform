<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;

/**
 * Add collection support to audit log
 */
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
        $this->addEntityAddedToCollectionWithChangeSet($entityClass, $entityId, $entityName);
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     * @param string $entityName
     * @param array $changeSet
     */
    public function addEntityAddedToCollectionWithChangeSet($entityClass, $entityId, $entityName, array $changeSet = [])
    {
        $this->setNewValue(null);
        $this->setOldValue(null);
        $this->dataType = AuditFieldTypeRegistry::getAuditType('collection');

        $thisCollectionDiffs = $this->getCollectionDiffs();
        $thisCollectionDiffs['added'][$entityClass.$entityId] = [
            'entity_class' => $entityClass,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'change_set' => $changeSet,
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
        $this->addEntityRemovedFromCollectionWithChangeSet($entityClass, $entityId, $entityName);
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     * @param string $entityName
     * @param array $changeSet
     */
    public function addEntityRemovedFromCollectionWithChangeSet(
        $entityClass,
        $entityId,
        $entityName,
        array $changeSet = []
    ) {
        $this->setNewValue(null);
        $this->setOldValue(null);
        $this->dataType = AuditFieldTypeRegistry::getAuditType('collection');

        $thisCollectionDiffs = $this->getCollectionDiffs();
        $thisCollectionDiffs['removed'][$entityClass.$entityId] = [
            'entity_class' => $entityClass,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'change_set' => $changeSet,
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
        $this->addEntityChangedInCollectionWithChangeSet($entityClass, $entityId, $entityName);
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     * @param string $entityName
     * @param array $changeSet
     */
    public function addEntityChangedInCollectionWithChangeSet(
        $entityClass,
        $entityId,
        $entityName,
        array $changeSet = []
    ) {
        $this->setNewValue(null);
        $this->setOldValue(null);
        $this->dataType = AuditFieldTypeRegistry::getAuditType('collection');

        $thisCollectionDiffs = $this->getCollectionDiffs();

        $key = 'changed';
        if (!empty($thisCollectionDiffs['added'][$entityClass.$entityId])) {
            $key = 'added';
        }

        $thisCollectionDiffs[$key][$entityClass.$entityId] = [
            'entity_class' => $entityClass,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'change_set' => $changeSet,
        ];

        $this->collectionDiffs = $thisCollectionDiffs;
    }

    /**
     * @internal
     */
    public function calculateNewValue()
    {
        $thisCollectionDiffs = $this->getCollectionDiffs();

        $newValue = null;
        if ($thisCollectionDiffs['added']) {
            $newValue .= 'Added: '.
                implode(
                    ', ',
                    array_map(
                        function (array $entityData) {
                            return $entityData['entity_name'];
                        },
                        $thisCollectionDiffs['added']
                    )
                );
        }
        if ($thisCollectionDiffs['changed']) {
            $newValue .= "\nChanged: ".
                implode(
                    ', ',
                    array_map(
                        function (array $entityData) {
                            return $entityData['entity_name'];
                        },
                        $thisCollectionDiffs['changed']
                    )
                );
        }
        $this->setNewValue($newValue);

        $oldValue = null;
        if ($thisCollectionDiffs['removed']) {
            $oldValue .= "Removed: ".
                implode(
                    ', ',
                    array_map(
                        function (array $entityData) {
                            return $entityData['entity_name'];
                        },
                        $thisCollectionDiffs['removed']
                    )
                );
        }
        $this->setOldValue($oldValue);
    }

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
