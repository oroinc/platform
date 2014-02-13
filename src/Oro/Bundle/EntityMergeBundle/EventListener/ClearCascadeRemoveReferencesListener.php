<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;

/**
 * Clear references to selected value from other entities, because it will be deleted according to cascade remove logic.
 */
class ClearCascadeRemoveReferencesListener
{
    /**
     * @var AccessorInterface
     */
    protected $accessor;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param AccessorInterface $accessor
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(AccessorInterface $accessor, DoctrineHelper $doctrineHelper)
    {
        $this->accessor = $accessor;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param FieldDataEvent $event
     */
    public function afterMergeField(FieldDataEvent $event)
    {
        $fieldData = $event->getFieldData();
        $metadata = $fieldData->getMetadata();

        if (MergeModes::REPLACE != $fieldData->getMode() ||
            !$metadata->hasDoctrineMetadata() ||
            !$metadata->getDoctrineMetadata()->isMappedBySourceEntity() ||
            !$metadata->getDoctrineMetadata()->isAssociation() ||
            !in_array('remove', (array)$metadata->getDoctrineMetadata()->get('cascade'))
        ) {
            return;
        }

        $entitiesToClear = $this->getEntitiesToClear($fieldData->getEntityData());
        foreach ($entitiesToClear as $entityToClear) {
            $this->accessor->setValue($entityToClear, $metadata, null);
        }
    }

    /**
     * Get entities that are not master.
     *
     * @param EntityData $entityData
     * @return array
     */
    protected function getEntitiesToClear(EntityData $entityData)
    {
        $result = array();

        $masterEntity = $entityData->getMasterEntity();

        foreach ($entityData->getEntities() as $entity) {
            if (!$this->doctrineHelper->isEntityEqual($masterEntity, $entity)) {
                $result[] = $entity;
            }
        }

        return $result;
    }
}
