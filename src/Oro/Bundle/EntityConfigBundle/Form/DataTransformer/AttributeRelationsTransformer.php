<?php

namespace Oro\Bundle\EntityConfigBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Form data transformer for AttributeMultiSelectType
 */
class AttributeRelationsTransformer implements DataTransformerInterface
{
    /** @var \Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup|null */
    private $attributeGroup;

    /**
     * @param AttributeGroup|null $attributeGroup
     */
    public function __construct($attributeGroup)
    {
        $this->attributeGroup = $attributeGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($collectionToArray)
    {
        if (null === $collectionToArray) {
            return [];
        }

        $selected = [];
        /** @var AttributeGroupRelation $relation */
        foreach ($collectionToArray as $relation) {
            $selected[] = $relation->getEntityConfigFieldId();
        }

        return $selected;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($arrayToCollection)
    {
        // New group(null) passed - create empty instance
        if (!$this->attributeGroup instanceof AttributeGroup) {
            $this->attributeGroup = new AttributeGroup();
        }

        $attributeGroupRelations = $this->attributeGroup->getAttributeRelations();
        $newAttributeGroupRelations = new ArrayCollection();
        $replaceWholeCollection = false;

        foreach ($arrayToCollection as $i => $attributeId) {
            // Adds to new collection in case it will be needed to replace the old one.
            $newAttributeGroupRelations[$i] = new AttributeGroupRelation();
            $newAttributeGroupRelations[$i]->setEntityConfigFieldId($attributeId);
            $newAttributeGroupRelations[$i]->setAttributeGroup($this->attributeGroup);

            if (!$replaceWholeCollection) {
                if (!$attributeGroupRelations->offsetExists($i)) {
                    // Adds to old collection if it should not be replaced.
                    $this->attributeGroup->addAttributeRelation($newAttributeGroupRelations[$i]);
                } elseif ($attributeGroupRelations[$i]->getEntityConfigFieldId() !== $attributeId) {
                    // If attributes order is changed, then it is needed to replace whole collection to avoid constraint
                    // violation errors on saving.
                    $replaceWholeCollection = true;
                }
            }
        }

        if ($replaceWholeCollection) {
            $this->attributeGroup->setAttributeRelations($newAttributeGroupRelations);
        } else {
            /** @var AttributeGroupRelation $relation */
            foreach ($this->attributeGroup->getAttributeRelations() as $relation) {
                $attributeId = $relation->getEntityConfigFieldId();
                //Attribute was removed in UI - remove it from collection
                if (!in_array($attributeId, $arrayToCollection, true)) {
                    $this->attributeGroup->removeAttributeRelation($relation);
                    continue;
                }
            }
        }

        return $this->attributeGroup->getAttributeRelations();
    }
}
