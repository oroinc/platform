<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Mapper;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

abstract class AbstractAttributeBlockTypeMapper implements AttributeBlockTypeMapperInterface
{
    /** @var array */
    private $attributeTypesRegistry;

    /** @var AttributeBlockTypeMapperInterface[] */
    private $providers;

    /**
     * @param AttributeBlockTypeMapperInterface $provider
     *
     * @return AbstractAttributeBlockTypeMapper
     */
    public function addProvider(AttributeBlockTypeMapperInterface $provider)
    {
        $this->providers[] = $provider;

        return $this;
    }

    /**
     * @param string $attributeType
     * @param string $blockType
     *
     * @return AbstractAttributeBlockTypeMapper
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

        foreach ($this->providers as $provider) {
            $blockType = $provider->getBlockType($attribute);
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
}
