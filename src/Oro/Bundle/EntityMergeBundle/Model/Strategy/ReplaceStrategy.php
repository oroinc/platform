<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

class ReplaceStrategy implements StrategyInterface
{
    /**
     * @var AccessorInterface $accessor
     */
    protected $accessor;

    /**
     * @param AccessorInterface $accessor
     */
    public function __construct(AccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function supports(FieldData $fieldData)
    {
        return $fieldData->getMode() == MergeModes::REPLACE;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'replace';
    }
}
