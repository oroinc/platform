<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class EntityMetadataFactory
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ClassMetadata $classMetadata
     *
     * @return EntityMetadata
     */
    public function createEntityMetadata(ClassMetadata $classMetadata)
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($classMetadata->name);
        $entityMetadata->setIdentifierFieldNames($classMetadata->getIdentifierFieldNames());
        $entityMetadata->setHasIdentifierGenerator($classMetadata->usesIdGenerator());
        $entityMetadata->setInheritedType($classMetadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE);

        return $entityMetadata;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param string        $fieldName
     * @param string|null   $fieldType
     *
     * @return MetaPropertyMetadata
     */
    public function createMetaPropertyMetadata(ClassMetadata $classMetadata, $fieldName, $fieldType = null)
    {
        if (!$fieldType) {
            $fieldType = (string)$classMetadata->getTypeOfField($fieldName);
        }
        $fieldMetadata = new MetaPropertyMetadata();
        $fieldMetadata->setName($fieldName);
        $fieldMetadata->setDataType($fieldType);

        return $fieldMetadata;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param string        $fieldName
     * @param string|null   $fieldType
     *
     * @return FieldMetadata
     */
    public function createFieldMetadata(ClassMetadata $classMetadata, $fieldName, $fieldType = null)
    {
        if (!$fieldType) {
            $fieldType = (string)$classMetadata->getTypeOfField($fieldName);
        }
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);
        $fieldMetadata->setDataType($fieldType);
        $fieldMetadata->setIsNullable($classMetadata->isNullable($fieldName));
        $mapping = $classMetadata->getFieldMapping($fieldName);
        if (isset($mapping['length'])) {
            $fieldMetadata->setMaxLength($mapping['length']);
        }

        return $fieldMetadata;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param string        $associationName
     * @param string|null   $associationDataType
     *
     * @return AssociationMetadata
     */
    public function createAssociationMetadata(
        ClassMetadata $classMetadata,
        $associationName,
        $associationDataType = null
    ) {
        $targetClass = $classMetadata->getAssociationTargetClass($associationName);

        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setIsCollection($classMetadata->isCollectionValuedAssociation($associationName));

        $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass);
        if ($associationDataType) {
            $associationMetadata->setDataType($associationDataType);
        } else {
            $targetIdFields = $targetMetadata->getIdentifierFieldNames();
            if (count($targetIdFields) === 1) {
                $associationMetadata->setDataType($targetMetadata->getTypeOfField(reset($targetIdFields)));
            } else {
                $associationMetadata->setDataType(DataType::STRING);
            }
        }

        if ($targetMetadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
            $associationMetadata->setAcceptableTargetClassNames($targetMetadata->subClasses);
        } else {
            $associationMetadata->addAcceptableTargetClassName($targetClass);
        }

        $isNullable = true;
        if ($classMetadata->isAssociationWithSingleJoinColumn($associationName)) {
            $mapping = $classMetadata->getAssociationMapping($associationName);
            if (!isset($mapping['joinColumns'][0]['nullable']) || !$mapping['joinColumns'][0]['nullable']) {
                $isNullable = false;
            }
        }
        $associationMetadata->setIsNullable($isNullable);

        return $associationMetadata;
    }
}
