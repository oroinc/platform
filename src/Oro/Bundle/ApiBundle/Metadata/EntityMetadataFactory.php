<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * The factory to create Data API metadata based on ORM metadata.
 */
class EntityMetadataFactory
{
    private const TYPE          = 'type';
    private const NULLABLE      = 'nullable';
    private const LENGTH        = 'length';
    private const TARGET_ENTITY = 'targetEntity';
    private const JOIN_COLUMNS  = 'joinColumns';

    /** @var DoctrineHelper */
    private $doctrineHelper;

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
    public function createEntityMetadata(ClassMetadata $classMetadata): EntityMetadata
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($classMetadata->name);
        $entityMetadata->setIdentifierFieldNames($classMetadata->getIdentifierFieldNames());
        $entityMetadata->setHasIdentifierGenerator($classMetadata->usesIdGenerator());
        $entityMetadata->setInheritedType(!$classMetadata->isInheritanceTypeNone());

        return $entityMetadata;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param string        $propertyPath
     * @param string|null   $fieldType
     *
     * @return MetaPropertyMetadata
     */
    public function createMetaPropertyMetadata(
        ClassMetadata $classMetadata,
        string $propertyPath,
        string $fieldType = null
    ): MetaPropertyMetadata {
        /** @var ClassMetadata $classMetadata */
        /** @var string $fieldName */
        list($classMetadata, $fieldName) = $this->getTargetClassMetadata($classMetadata, $propertyPath);
        if (!$fieldType && $classMetadata->hasField($fieldName)) {
            $fieldType = $this->getFieldType($classMetadata->getFieldMapping($fieldName));
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
    public function createFieldMetadata(
        ClassMetadata $classMetadata,
        string $propertyPath,
        string $fieldType = null
    ): FieldMetadata {
        /** @var ClassMetadata $classMetadata */
        /** @var string $fieldName */
        list($classMetadata, $fieldName) = $this->getTargetClassMetadata($classMetadata, $propertyPath);
        $mapping = $classMetadata->getFieldMapping($fieldName);
        if (!$fieldType) {
            $fieldType = $this->getFieldType($mapping);
        }
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);
        $fieldMetadata->setDataType($fieldType);
        if (!empty($mapping[self::NULLABLE])) {
            $fieldMetadata->setIsNullable(true);
        }
        if (isset($mapping[self::LENGTH])) {
            $fieldMetadata->setMaxLength($mapping[self::LENGTH]);
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
        string $propertyPath,
        string $associationDataType = null
    ): AssociationMetadata {
        /** @var ClassMetadata $classMetadata */
        /** @var string $associationName */
        list($classMetadata, $associationName) = $this->getTargetClassMetadata($classMetadata, $propertyPath);
        $mapping = $classMetadata->getAssociationMapping($associationName);

        $targetClass = $mapping[self::TARGET_ENTITY];

        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setAssociationType($this->getAssociationType($mapping));
        if (!($mapping[self::TYPE] & ClassMetadata::TO_ONE)) {
            $associationMetadata->setIsCollection(true);
        }
        if ($this->isNullableAssociation($mapping)) {
            $associationMetadata->setIsNullable(true);
        }

        /** @var ClassMetadata $targetMetadata */
        $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass);
        if ($associationDataType) {
            $associationMetadata->setDataType($associationDataType);
        } else {
            $targetIdFields = $targetMetadata->getIdentifierFieldNames();
            if (count($targetIdFields) === 1) {
                $associationMetadata->setDataType(
                    $this->getFieldType($targetMetadata->getFieldMapping(\reset($targetIdFields)))
                );
            } else {
                $associationMetadata->setDataType(DataType::STRING);
            }
        }
        if ($targetMetadata->isInheritanceTypeNone()) {
            $associationMetadata->addAcceptableTargetClassName($targetClass);
        } else {
            $associationMetadata->setAcceptableTargetClassNames($targetMetadata->subClasses);
        }

        return $associationMetadata;
    }

    /**
     * @param array $fieldMapping
     *
     * @return string
     */
    private function getFieldType(array $fieldMapping): string
    {
        return (string)$fieldMapping[self::TYPE];
    }

    /**
     * @param array $associationMapping
     *
     * @return string|null
     */
    private function getAssociationType(array $associationMapping): ?string
    {
        switch ($associationMapping[self::TYPE]) {
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
     * @param array $associationMapping
     *
     * @return bool
     */
    private function isNullableAssociation(array $associationMapping): bool
    {
        $isNullable = true;
        // check "nullable" option for a single join column association only
        if (isset($associationMapping[self::JOIN_COLUMNS][0]) && !isset($associationMapping[self::JOIN_COLUMNS][1])) {
            $joinColumn = $associationMapping[self::JOIN_COLUMNS][0];
            if (!isset($joinColumn[self::NULLABLE]) || !$joinColumn[self::NULLABLE]) {
                $isNullable = false;
            }
        }

        return $isNullable;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param string        $propertyPath
     *
     * @return array [target class metadata, target field name]
     *
     * @throws \InvalidArgumentException if the target class metadata cannot be found
     */
    private function getTargetClassMetadata(ClassMetadata $classMetadata, string $propertyPath): array
    {
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        if (count($path) === 1) {
            return [$classMetadata, $propertyPath];
        }

        $fieldName = \array_pop($path);
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
            throw new \InvalidArgumentException(\sprintf(
                'Cannot find metadata by path "%s" starting with class "%s"',
                \implode(ConfigUtil::PATH_DELIMITER, $path),
                $classMetadata->name
            ));
        }

        return [$targetClassMetadata, $fieldName];
    }
}
