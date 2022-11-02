<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Psr\Container\ContainerInterface;

/**
 * The registry of attribute types.
 */
class AttributeTypeRegistry
{
    /** @var ContainerInterface */
    private $attributeTypes;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(ContainerInterface $attributeTypes, DoctrineHelper $doctrineHelper)
    {
        $this->attributeTypes = $attributeTypes;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return AttributeTypeInterface|null
     */
    public function getAttributeType(FieldConfigModel $attribute)
    {
        $type = $attribute->getType();
        if (!$this->attributeTypes->has($type)) {
            $type = $this->getType($attribute);
        }

        if ($type && $this->attributeTypes->has($type)) {
            return $this->attributeTypes->get($type);
        }

        return null;
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return string|null
     */
    private function getType(FieldConfigModel $attribute)
    {
        $className = $attribute->getEntity()->getClassName();
        $fieldName = $attribute->getFieldName();

        $metadata = $this->doctrineHelper->getEntityMetadata($className);

        if ($metadata->hasField($fieldName)) {
            return $metadata->getTypeOfField($fieldName);
        }

        if ($metadata->hasAssociation($fieldName)) {
            return $this->getAssociationTypeOptions($metadata, $fieldName);
        }

        return null;
    }

    /**
     * @param ClassMetadata $metadata
     * @param string $fieldName
     *
     * @return string
     */
    private function getAssociationTypeOptions(ClassMetadata $metadata, $fieldName)
    {
        $fieldInfo = $metadata->getAssociationMapping($fieldName);

        switch ($fieldInfo['type']) {
            case ClassMetadataInfo::ONE_TO_ONE:
                return RelationType::ONE_TO_ONE;
            case ClassMetadataInfo::MANY_TO_ONE:
                return RelationType::MANY_TO_ONE;
            case ClassMetadataInfo::ONE_TO_MANY:
                return RelationType::ONE_TO_MANY;
            case ClassMetadataInfo::MANY_TO_MANY:
                return RelationType::MANY_TO_MANY;
            default:
                return null;
        }
    }
}
