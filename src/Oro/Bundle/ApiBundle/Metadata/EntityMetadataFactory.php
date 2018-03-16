<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

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
     * @param string        $propertyPath
     * @param string|null   $fieldType
     *
     * @return MetaPropertyMetadata
     */
    public function createMetaPropertyMetadata(ClassMetadata $classMetadata, $propertyPath, $fieldType = null)
    {
        list($classMetadata, $fieldName) = $this->getTargetClassMetadata($classMetadata, $propertyPath);
        if (!$fieldType && $classMetadata->hasField($fieldName)) {
            $fieldType = (string)$classMetadata->getTypeOfField($fieldName);
        }
        $fieldMetadata = new MetaPropertyMetadata();
        $fieldMetadata->setName($fieldName);
        $fieldMetadata->setDataType($fieldType);

        return $fieldMetadata;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param string        $propertyPath
     * @param string|null   $fieldType
     *
     * @return FieldMetadata
     */
    public function createFieldMetadata(ClassMetadata $classMetadata, $propertyPath, $fieldType = null)
    {
        list($classMetadata, $fieldName) = $this->getTargetClassMetadata($classMetadata, $propertyPath);
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
     * @param string        $propertyPath
     * @param string|null   $associationDataType
     *
     * @return AssociationMetadata
     */
    public function createAssociationMetadata(
        ClassMetadata $classMetadata,
        $propertyPath,
        $associationDataType = null
    ) {
        list($classMetadata, $associationName) = $this->getTargetClassMetadata($classMetadata, $propertyPath);
        $targetClass = $classMetadata->getAssociationTargetClass($associationName);

        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setAssociationType(
            $this->getAssociationType($classMetadata->getAssociationMapping($associationName))
        );
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

    /**
     * @param array $associationMapping
     *
     * @return string|null
     */
    protected function getAssociationType(array $associationMapping)
    {
        switch ($associationMapping['type']) {
            case ClassMetadata::MANY_TO_ONE:
                return RelationType::MANY_TO_ONE;
            case ClassMetadata::MANY_TO_MANY:
                return RelationType::MANY_TO_MANY;
            case ClassMetadata::ONE_TO_MANY:
                return RelationType::ONE_TO_MANY;
            case ClassMetadata::ONE_TO_ONE:
                return RelationType::ONE_TO_ONE;
            default:
                return null;
        }
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param string        $propertyPath
     *
     * @return array [target class metadata, target field name]
     *
     * @throws \InvalidArgumentException if the target class metadata cannot be found
     */
    protected function getTargetClassMetadata(ClassMetadata $classMetadata, $propertyPath)
    {
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        if (count($path) === 1) {
            return [$classMetadata, $propertyPath];
        }

        $fieldName = array_pop($path);
        $targetClassMetadata = $classMetadata;
        foreach ($path as $associationName) {
            if (!$targetClassMetadata->hasAssociation($associationName)) {
                $targetClassMetadata = null;
                break;
            }
            $targetClassMetadata = $this->doctrineHelper->getEntityMetadataForClass(
                $targetClassMetadata->getAssociationTargetClass($associationName)
            );
        }
        if (null === $targetClassMetadata) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot find metadata by path "%s" starting with class "%s"',
                implode(ConfigUtil::PATH_DELIMITER, $path),
                $classMetadata->name
            ));
        }

        return [$targetClassMetadata, $fieldName];
    }
}
