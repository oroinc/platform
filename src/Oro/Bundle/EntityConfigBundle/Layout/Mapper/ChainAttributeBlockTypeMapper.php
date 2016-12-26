<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Mapper;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class ChainAttributeBlockTypeMapper implements AttributeBlockTypeMapperInterface
{
    /** @var array */
    private $attributeTypesRegistry = [];

    /** @var AttributeBlockTypeMapperInterface[] */
    private $mappers;

    /**
     * @param AttributeBlockTypeMapperInterface $mapper
     *
     * @return ChainAttributeBlockTypeMapper
     */
    public function addMapper(AttributeBlockTypeMapperInterface $mapper)
    {
        $this->mappers[] = $mapper;

        return $this;
    }

    /**
     * @param string $attributeType
     * @param string $blockType
     *
     * @return ChainAttributeBlockTypeMapper
     */
    public function addBlockType($attributeType, $blockType)
    {
        $this->attributeTypesRegistry[$attributeType] = $blockType;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException
     */
    public function getBlockType(FieldConfigModel $attribute)
    {
        $type = $attribute->getType();

        foreach ($this->mappers as $mapper) {
            if (!$mapper->supports($attribute)) {
                continue;
            }

            $blockType = $mapper->getBlockType($attribute);
            if ($blockType !== null) {
                return $blockType;
            }
        }

        if (array_key_exists($type, $this->attributeTypesRegistry)) {
            return $this->attributeTypesRegistry[$type];
        }

        $fieldName = $attribute->getFieldName();
        throw new \LogicException(sprintf('Block type is not define for field "%s"', $fieldName));
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return boolean
     */
    public function supports(FieldConfigModel $attribute)
    {
        return true;
    }
}
