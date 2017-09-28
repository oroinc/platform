<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class AttributeTypeRegistry
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ArrayCollection */
    protected $attributeTypes;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->attributeTypes = new ArrayCollection();
    }

    /**
     * @param AttributeTypeInterface $attributeType
     */
    public function addAttributeType(AttributeTypeInterface $attributeType)
    {
        $this->attributeTypes->set($attributeType->getType(), $attributeType);
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return null|AttributeTypeInterface
     */
    public function getAttributeType(FieldConfigModel $attribute)
    {
        $type = $attribute->getType();
        if (!$this->attributeTypes->containsKey($type)) {
            $type = $this->getType($attribute);
        }

        return $this->attributeTypes->get($type);
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return null|string
     */
    protected function getType(FieldConfigModel $attribute)
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
