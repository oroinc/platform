<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Mapper;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Maps attribute types and target classes to layout block types.
 *
 * This mapper provides a registry-based approach to determine which layout block type should be used
 * to render a specific attribute. It supports mapping by attribute type directly or by inspecting the
 * target class of association attributes using Doctrine metadata.
 */
class AbstractAttributeBlockTypeMapper implements AttributeBlockTypeMapperInterface
{
    /** @var array */
    protected $attributeTypesRegistry = [];

    /** @var array */
    protected $targetClassesRegistry = [];

    /** @var ManagerRegistry */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
     * @param string $targetClass
     * @param string $blockType
     *
     * @return AbstractAttributeBlockTypeMapper
     */
    public function addBlockTypeUsingMetadata($targetClass, $blockType)
    {
        $this->targetClassesRegistry[$targetClass] = $blockType;

        return $this;
    }

    #[\Override]
    public function getBlockType(FieldConfigModel $attribute)
    {
        $type = $attribute->getType();
        if (array_key_exists($type, $this->attributeTypesRegistry)) {
            return $this->attributeTypesRegistry[$type];
        }

        if (0 !== count($this->targetClassesRegistry)) {
            $className = $attribute->getEntity()->getClassName();
            $em = $this->registry->getManagerForClass($className);
            $metadata = $em->getClassMetadata($className);
            $fieldName = $attribute->getFieldName();
            foreach ($metadata->getAssociationNames() as $associationName) {
                if ($associationName !== $fieldName) {
                    continue;
                }

                $targetClass = $metadata->getAssociationTargetClass($associationName);
                if (array_key_exists($targetClass, $this->targetClassesRegistry)) {
                    return $this->targetClassesRegistry[$targetClass];
                }
            }
        }

        return null;
    }
}
