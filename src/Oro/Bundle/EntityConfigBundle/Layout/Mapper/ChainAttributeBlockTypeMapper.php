<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Mapper;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class ChainAttributeBlockTypeMapper implements AttributeBlockTypeMapperInterface
{
    /** @var string  */
    protected $defaultBlockType;

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
     * @param string $blockType
     */
    public function setDefaultBlockType($blockType)
    {
        $this->defaultBlockType = $blockType;
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
            $blockType = $mapper->getBlockType($attribute);
            if ($blockType !== null) {
                return $blockType;
            }
        }

        if (array_key_exists($type, $this->attributeTypesRegistry)) {
            return $this->attributeTypesRegistry[$type];
        }

        return $this->defaultBlockType;
    }
}
