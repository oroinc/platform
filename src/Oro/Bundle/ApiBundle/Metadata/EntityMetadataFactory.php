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
        $entityMetadata->setInheritedType($classMetadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE);

        return $entityMetadata;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param string        $fieldName
     *
     * @return FieldMetadata
     */
    public function createFieldMetadata(ClassMetadata $classMetadata, $fieldName)
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);
        $fieldMetadata->setDataType($classMetadata->getTypeOfField($fieldName));

        return $fieldMetadata;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param string        $associationName
     *
     * @return AssociationMetadata
     */
    public function createAssociationMetadata(ClassMetadata $classMetadata, $associationName)
    {
        $targetClass = $classMetadata->getAssociationTargetClass($associationName);

        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setIsCollection($classMetadata->isCollectionValuedAssociation($associationName));

        $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass);
        $targetIdFields = $targetMetadata->getIdentifierFieldNames();
        if (count($targetIdFields) === 1) {
            $associationMetadata->setDataType($targetMetadata->getTypeOfField(reset($targetIdFields)));
        } else {
            $associationMetadata->setDataType(DataType::STRING);
        }

        if ($targetMetadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
            $associationMetadata->setAcceptableTargetClassNames($targetMetadata->subClasses);
        } else {
            $associationMetadata->addAcceptableTargetClassName($targetClass);
        }

        return $associationMetadata;
    }
}
