<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Mapper;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class ChainAttributeBlockTypeMapper extends AbstractAttributeBlockTypeMapper
{
    /** @var string */
    private $defaultBlockType;

    /** @var AttributeBlockTypeMapperInterface[] */
    private $mappers = [];

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
     * @param string $blockType
     */
    public function setDefaultBlockType($blockType)
    {
        $this->defaultBlockType = $blockType;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockType(FieldConfigModel $attribute)
    {
        foreach ($this->mappers as $mapper) {
            $blockType = $mapper->getBlockType($attribute);
            if ($blockType !== null) {
                return $blockType;
            }
        }

        $blockType = parent::getBlockType($attribute);
        if (null !== $blockType) {
            return $blockType;
        }

        return $this->defaultBlockType;
    }
}
