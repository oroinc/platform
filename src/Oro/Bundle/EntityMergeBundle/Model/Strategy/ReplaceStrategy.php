<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

/**
 * Implements the replace merge strategy for entity fields.
 *
 * Replaces the master entity's field value with the value from the selected source entity.
 * For fields marked as needing cloning, creates a deep copy of the value to prevent shared
 * references. If no source entity is explicitly selected, uses the master entity's value.
 * This strategy is applicable to all field types.
 */
class ReplaceStrategy implements StrategyInterface
{
    /**
     * @var AccessorInterface $accessor
     */
    protected $accessor;

    public function __construct(AccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    #[\Override]
    public function merge(FieldData $fieldData)
    {
        $entityData    = $fieldData->getEntityData();
        $masterEntity  = $entityData->getMasterEntity();
        $fieldMetadata = $fieldData->getMetadata();
        $sourceEntity  = $fieldData->getSourceEntity();

        //for fields that are not in the merge form(system, custom etc) use values from Master record
        if (!$sourceEntity) {
            $sourceEntity = $masterEntity;
        }

        $value = $this->accessor->getValue($sourceEntity, $fieldMetadata);

        if ($fieldMetadata->shouldBeCloned()) {
            $value = $value->map(function ($element) {
                return clone $element;
            });
        }

        $this->accessor->setValue($masterEntity, $fieldMetadata, $value);
    }

    #[\Override]
    public function supports(FieldData $fieldData)
    {
        return $fieldData->getMode() == MergeModes::REPLACE;
    }

    #[\Override]
    public function getName()
    {
        return 'replace';
    }
}
