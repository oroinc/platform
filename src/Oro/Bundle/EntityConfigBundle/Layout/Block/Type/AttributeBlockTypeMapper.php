<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class AttributeBlockTypeMapper
{
    /** @var array */
    private $attributeTypesRegistry;

    /** @var array */
    private $entityFieldTypesRegistry;

    /**
     * @param string $attributeType
     * @param string $blockType
     * @return $this
     */
    public function addBlockTypeByAttributeType($attributeType, $blockType)
    {
        $this->attributeTypesRegistry[$attributeType] = $blockType;

        return $this;
    }

    /**
     * @param string $entityClass
     * @param string $entityField
     * @param string $blockType
     * @return $this
     */
    public function addBlockTypeByEntityAndField($entityClass, $entityField, $blockType)
    {
        $this->entityFieldTypesRegistry[$entityClass][$entityField] = $blockType;

        return $this;
    }

    /**
     * @param FieldConfigModel $attribute
     * @return string
     * @throws \LogicException
     */
    public function getBlockType(FieldConfigModel $attribute)
    {
        $class = $attribute->getEntity()->getClassName();
        $fieldName = $attribute->getFieldName();
        $type = $attribute->getType();

        if (isset($this->entityFieldTypesRegistry[$class][$fieldName])) {
            return $this->entityFieldTypesRegistry[$class][$fieldName];
        } elseif (isset($this->attributeTypesRegistry[$type])) {
            return $this->attributeTypesRegistry[$type];
        }

        throw new \LogicException('Block type is not define for field - ' . $fieldName);
    }
}
