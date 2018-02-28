<?php

namespace Oro\Bundle\EntityConfigBundle\Form\DataTransformer;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Symfony\Component\Form\DataTransformerInterface;

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
        $existingRelations = [];

        //Existing group passed
        if ($this->attributeGroup instanceof AttributeGroup) {
            /** @var AttributeGroupRelation $relation */
            foreach ($this->attributeGroup->getAttributeRelations() as $relation) {
                $attributeId = $relation->getEntityConfigFieldId();
                //Attribute was removed in UI - remove it from collection
                if (!in_array($attributeId, $arrayToCollection, true)) {
                    $this->attributeGroup->removeAttributeRelation($relation);
                    continue;
                }
                $existingRelations[] = $attributeId;
            }
        } else { //New group(null) passed - create empty instance
            $this->attributeGroup = new AttributeGroup();
        }

        foreach ($arrayToCollection as $attributeId) {
            //Such attribute and relation already assigned
            if (in_array($attributeId, $existingRelations, true)) {
                continue;
            }
            //Create new relation
            $relation = new AttributeGroupRelation();
            $relation->setEntityConfigFieldId($attributeId);
            $this->attributeGroup->addAttributeRelation($relation);
        }

        return $this->attributeGroup->getAttributeRelations();
    }
}
