<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Mapper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class AbstractAttributeBlockTypeMapper implements AttributeBlockTypeMapperInterface
{
    /** @var array */
    protected $attributeTypesRegistry = [];

    /** @var array */
    protected $targetClassesRegistry = [];

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
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

    /**
     * {@inheritdoc}
     */
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
