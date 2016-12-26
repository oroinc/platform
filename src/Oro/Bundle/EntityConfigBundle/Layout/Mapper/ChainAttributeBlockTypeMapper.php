<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Mapper;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class ChainAttributeBlockTypeMapper implements AttributeBlockTypeMapperInterface
{
    /** @var string  */
    protected $defaultBlockType;

    /** @var array */
    private $attributeTypesRegistry = [];

    /** @var array */
    private $targetClassesRegistry = [];

    /** @var AttributeBlockTypeMapperInterface[] */
    private $mappers;

    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

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
     * @param string $targetClass
     * @param string $blockType
     *
     * @return ChainAttributeBlockTypeMapper
     */
    public function addBlockTypeUsingMetadata($targetClass, $blockType)
    {
        $this->targetClassesRegistry[$targetClass] = $blockType;

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

        if (0 !== count($this->targetClassesRegistry)) {
            $className = $attribute->getEntity()->getClassName();
            $em = $this->registry->getManagerForClass($className);
            $metadata = $em->getClassMetadata($className);
            foreach ($metadata->getAssociationNames() as $associationName) {
                $targetClass = $metadata->getAssociationTargetClass($associationName);
                if (array_key_exists($targetClass, $this->targetClassesRegistry)) {
                    return $this->targetClassesRegistry[$targetClass];
                }
            }
        }

        return $this->defaultBlockType;
    }
}
