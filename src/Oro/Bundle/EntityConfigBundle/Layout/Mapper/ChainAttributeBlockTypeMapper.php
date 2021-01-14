<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Mapper;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Uses configured mappers to find a layout block type for an attribute.
 */
class ChainAttributeBlockTypeMapper extends AbstractAttributeBlockTypeMapper
{
    /** @var string */
    private $defaultBlockType;

    /** @var iterable|AttributeBlockTypeMapperInterface[] */
    private $mappers;

    /**
     * @param ManagerRegistry $registry
     * @param iterable|AttributeBlockTypeMapperInterface[] $mappers
     */
    public function __construct(ManagerRegistry $registry, iterable $mappers)
    {
        parent::__construct($registry);
        $this->mappers = $mappers;
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
