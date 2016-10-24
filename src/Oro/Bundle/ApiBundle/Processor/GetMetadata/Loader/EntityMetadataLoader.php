<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class EntityMetadataLoader
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityMetadataFactory */
    protected $entityMetadataFactory;

    /** @var ObjectMetadataBuilder */
    protected $objectMetadataBuilder;

    /** @var EntityMetadataBuilder */
    protected $entityMetadataBuilder;

    /** @var EntityNestedObjectMetadataBuilder */
    protected $nestedObjectMetadataBuilder;

    /**
     * @param DoctrineHelper                    $doctrineHelper
     * @param EntityMetadataFactory             $entityMetadataFactory
     * @param ObjectMetadataBuilder             $objectMetadataBuilder
     * @param EntityMetadataBuilder             $entityMetadataBuilder
     * @param EntityNestedObjectMetadataBuilder $nestedObjectMetadataBuilder
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityMetadataFactory $entityMetadataFactory,
        ObjectMetadataBuilder $objectMetadataBuilder,
        EntityMetadataBuilder $entityMetadataBuilder,
        EntityNestedObjectMetadataBuilder $nestedObjectMetadataBuilder
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityMetadataFactory = $entityMetadataFactory;
        $this->objectMetadataBuilder = $objectMetadataBuilder;
        $this->entityMetadataBuilder = $entityMetadataBuilder;
        $this->nestedObjectMetadataBuilder = $nestedObjectMetadataBuilder;
    }

    /**
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $config
     * @param bool                   $withExcludedProperties
     * @param string                 $targetAction
     *
     * @return EntityMetadata
     */
    public function loadEntityMetadata(
        $entityClass,
        EntityDefinitionConfig $config,
        $withExcludedProperties,
        $targetAction
    ) {
        // filter excluded fields on this stage though there is another processor doing the same
        // it is done due to performance reasons
        $allowedFields = $this->getAllowedFields($config, $withExcludedProperties);

        $classMetadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $entityMetadata = $this->createEntityMetadata($classMetadata, $config);
        $this->loadEntityFieldsMetadata(
            $entityMetadata,
            $classMetadata,
            $allowedFields,
            $config,
            $targetAction
        );
        $this->loadEntityAssociationsMetadata(
            $entityMetadata,
            $classMetadata,
            $allowedFields,
            $config,
            $targetAction
        );
        $this->loadEntityPropertiesMetadata(
            $entityMetadata,
            $classMetadata,
            $config,
            $withExcludedProperties,
            $targetAction
        );

        return $entityMetadata;
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param bool                   $withExcludedProperties
     *
     * @return array [property path => field name, ...]
     */
    protected function getAllowedFields(EntityDefinitionConfig $config, $withExcludedProperties)
    {
        $result = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($withExcludedProperties || !$field->isExcluded()) {
                $propertyPath = $field->getPropertyPath($fieldName);
                if (ConfigUtil::IGNORE_PROPERTY_PATH !== $propertyPath) {
                    $result[$propertyPath] = $fieldName;
                }
            }
        }

        return $result;
    }

    /**
     * @param ClassMetadata          $classMetadata
     * @param EntityDefinitionConfig $config
     *
     * @return EntityMetadata
     */
    protected function createEntityMetadata(ClassMetadata $classMetadata, EntityDefinitionConfig $config)
    {
        $entityMetadata = $this->entityMetadataFactory->createEntityMetadata($classMetadata);
        if ($config->hasFields()) {
            $idFieldNames = $entityMetadata->getIdentifierFieldNames();
            if (!empty($idFieldNames)) {
                $normalizedIdFieldNames = [];
                foreach ($idFieldNames as $propertyPath) {
                    $fieldName = $config->findFieldNameByPropertyPath($propertyPath);
                    $normalizedIdFieldNames[] = $fieldName ?: $propertyPath;
                }
                $entityMetadata->setIdentifierFieldNames($normalizedIdFieldNames);
            }
        }

        return $entityMetadata;
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param ClassMetadata          $classMetadata
     * @param array                  $allowedFields
     * @param EntityDefinitionConfig $config
     * @param string                 $targetAction
     */
    protected function loadEntityFieldsMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        array $allowedFields,
        EntityDefinitionConfig $config,
        $targetAction
    ) {
        $fields = $classMetadata->getFieldNames();
        foreach ($fields as $propertyPath) {
            if (!isset($allowedFields[$propertyPath])) {
                continue;
            }
            $fieldName = $allowedFields[$propertyPath];
            $field = $config->getField($fieldName);
            if ($field->isMetaProperty()) {
                $this->entityMetadataBuilder->addEntityMetaPropertyMetadata(
                    $entityMetadata,
                    $classMetadata,
                    $fieldName,
                    $field,
                    $targetAction
                );
            } else {
                $this->entityMetadataBuilder->addEntityFieldMetadata(
                    $entityMetadata,
                    $classMetadata,
                    $fieldName,
                    $field,
                    $targetAction
                );
            }
        }
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param ClassMetadata          $classMetadata
     * @param array                  $allowedFields
     * @param EntityDefinitionConfig $config
     * @param string                 $targetAction
     */
    protected function loadEntityAssociationsMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        array $allowedFields,
        EntityDefinitionConfig $config,
        $targetAction
    ) {
        $associations = $classMetadata->getAssociationNames();
        foreach ($associations as $propertyPath) {
            if (!isset($allowedFields[$propertyPath])) {
                continue;
            }
            $associationName = $allowedFields[$propertyPath];
            $this->entityMetadataBuilder->addEntityAssociationMetadata(
                $entityMetadata,
                $classMetadata,
                $associationName,
                $config->getField($associationName),
                $targetAction
            );
        }
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param ClassMetadata          $classMetadata
     * @param EntityDefinitionConfig $config
     * @param bool                   $withExcludedProperties
     * @param string                 $targetAction
     */
    protected function loadEntityPropertiesMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        EntityDefinitionConfig $config,
        $withExcludedProperties,
        $targetAction
    ) {
        $entityClass = $entityMetadata->getClassName();
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$withExcludedProperties && $field->isExcluded()) {
                continue;
            }
            if (!$field->isMetaProperty()) {
                $dataType = $field->getDataType();
                if ($dataType
                    && !$entityMetadata->hasField($fieldName)
                    && !$entityMetadata->hasAssociation($fieldName)
                ) {
                    $targetClass = $field->getTargetClass();
                    if ($targetClass) {
                        $this->objectMetadataBuilder->addAssociationMetadata(
                            $entityMetadata,
                            $entityClass,
                            $fieldName,
                            $field,
                            $targetAction
                        );
                    } elseif (DataType::isNestedObject($dataType)) {
                        $this->nestedObjectMetadataBuilder->addNestedObjectMetadata(
                            $entityMetadata,
                            $classMetadata,
                            $config,
                            $entityClass,
                            $fieldName,
                            $field,
                            $withExcludedProperties,
                            $targetAction
                        );
                    } else {
                        $this->objectMetadataBuilder->addFieldMetadata(
                            $entityMetadata,
                            $entityClass,
                            $fieldName,
                            $field,
                            $targetAction
                        );
                    }
                }
            } elseif (!$entityMetadata->hasMetaProperty($fieldName)) {
                $this->objectMetadataBuilder->addMetaPropertyMetadata(
                    $entityMetadata,
                    $entityClass,
                    $fieldName,
                    $field,
                    $targetAction
                );
            }
        }
    }
}
