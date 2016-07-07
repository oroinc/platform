<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\MergeField;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

/**
 * Clear stale references by master entity to source entities
 * which are going to be deleted, thus preventing dangling references to
 * deleted entities.
 * Applies to ManyToOne and OneToOne associations in replace mode.
 */
class StaleAssociationListener
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
        $entityData = $fieldData->getEntityData();
        $masterEntity = $entityData->getMasterEntity();

        if (!$this->shouldApply($fieldData)) {
            return;
        }

        $value = $this->accessor->getValue($masterEntity, $metadata);
        if (null === $value) {
            return;
        }

        if ($this->containsSourceEntity($entityData, $value)) {
            $this->accessor->setValue($masterEntity, $metadata, null);
        }
    }

    /**
     * @param  FieldData $fieldData
     * @return bool
     */
    protected function shouldApply(FieldData $fieldData)
    {
        $metadata = $fieldData->getMetadata();

        if (MergeModes::REPLACE === $fieldData->getMode() &&
            $metadata->hasDoctrineMetadata() &&
            $metadata->isDefinedBySourceEntity() &&
            $metadata->getDoctrineMetadata()->isAssociation() &&
            ($metadata->getDoctrineMetadata()->isManyToOne() ||
            $metadata->getDoctrineMetadata()->isOneToOne())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Searches the source entities in $entityData for a given entity.
     * Exludes master entity
     *
     * @param EntityData $entityData
     * @param object $needle The searched entity
     * @return bool
     */
    protected function containsSourceEntity(EntityData $entityData, $needle)
    {
        $masterEntity = $entityData->getMasterEntity();

        foreach ($entityData->getEntities() as $entity) {
            //skip master entity
            if ($this->doctrineHelper->isEntityEqual($masterEntity, $entity)) {
                continue;
            }

            if ($this->doctrineHelper->isEntityEqual($needle, $entity)) {
                return true;
            }
        }

        return false;
    }
}
