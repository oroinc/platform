<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Strategy;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

/**
 * Implements the unite merge strategy for collection fields.
 *
 * Combines collection values from all source entities into a single collection on the master entity,
 * removing duplicates based on entity identifiers. This strategy is only applicable to collection fields
 * when the `UNITE` merge mode is selected. For fields marked as needing cloning, creates deep copies of
 * collection elements to prevent shared references across entities.
 */
class UniteStrategy implements StrategyInterface
{
    /**
     * @var AccessorInterface $accessor
     */
    protected $accessor;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(
        AccessorInterface $accessor,
        DoctrineHelper $doctrineHelper
    ) {
        $this->accessor = $accessor;
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function merge(FieldData $fieldData)
    {
        $entityData    = $fieldData->getEntityData();
        $masterEntity  = $entityData->getMasterEntity();
        $fieldMetadata = $fieldData->getMetadata();
        $entities      = $entityData->getEntities();

        $relatedEntities         = [];
        foreach ($entities as $entity) {
            $values = $this->accessor->getValue($entity, $fieldMetadata);
            foreach ($values as $value) {
                $key = $this->doctrineHelper->getEntityIdentifierValue($value);
                $relatedEntities[$key] = $value;
            }
        }

        $collection = new ArrayCollection(array_values($relatedEntities));

        if ($fieldMetadata->shouldBeCloned()) {
            $collection = $collection->map(function ($element) {
                return clone $element;
            });
        }

        $this->accessor->setValue($masterEntity, $fieldMetadata, $collection);
    }

    #[\Override]
    public function supports(FieldData $fieldData)
    {
        if ($fieldData->getMode() == MergeModes::UNITE) {
            return $fieldData->getMetadata()->isCollection();
        }

        return false;
    }

    #[\Override]
    public function getName()
    {
        return 'unite';
    }
}
