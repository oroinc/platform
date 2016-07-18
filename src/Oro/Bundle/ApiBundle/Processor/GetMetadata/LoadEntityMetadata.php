<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Loads metadata for an entity.
 * This processor works with both ORM and not ORM entities.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class LoadEntityMetadata implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityMetadataFactory */
    protected $entityMetadataFactory;

    /**
     * @param DoctrineHelper        $doctrineHelper
     * @param EntityMetadataFactory $entityMetadataFactory
     */
    public function __construct(DoctrineHelper $doctrineHelper, EntityMetadataFactory $entityMetadataFactory)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityMetadataFactory = $entityMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var MetadataContext $context */

        if ($context->hasResult()) {
            // metadata is already loaded
            return;
        }

        $entityClass = $context->getClassName();
        $config = $context->getConfig();
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $context->setResult($this->loadEntityMetadata($entityClass, $config));
        } elseif ($config && $config->hasFields()) {
            $context->setResult($this->loadObjectMetadata($entityClass, $config));
        }
    }

    /**
     * @param string                      $entityClass
     * @param EntityDefinitionConfig|null $config
     *
     * @return EntityMetadata
     */
    protected function loadEntityMetadata($entityClass, EntityDefinitionConfig $config = null)
    {
        // filter excluded fields on this stage though there is another processor doing the same
        // it is done due to performance reasons
        $allowedFields = null !== $config
            ? $this->getAllowedFields($config)
            : [];

        $classMetadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $entityMetadata = $this->createEntityMetadata($classMetadata, $config);
        $this->loadFields($entityMetadata, $classMetadata, $allowedFields, $config);
        $this->loadAssociations($entityMetadata, $classMetadata, $allowedFields, $config);
        if (null !== $config && $config->hasFields()) {
            $this->loadPropertiesFromConfig($entityMetadata, $config);
        }

        return $entityMetadata;
    }

    /**
     * @param ClassMetadata               $classMetadata
     * @param EntityDefinitionConfig|null $config
     *
     * @return EntityMetadata
     */
    protected function createEntityMetadata(ClassMetadata $classMetadata, EntityDefinitionConfig $config = null)
    {
        $entityMetadata = $this->entityMetadataFactory->createEntityMetadata($classMetadata);
        if (null !== $config && $config->hasFields()) {
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
     * @param EntityMetadata              $entityMetadata
     * @param ClassMetadata               $classMetadata
     * @param array                       $allowedFields
     * @param EntityDefinitionConfig|null $config
     */
    protected function loadFields(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        array $allowedFields,
        EntityDefinitionConfig $config = null
    ) {
        $hasConfig = null !== $config;
        $fields = $classMetadata->getFieldNames();
        foreach ($fields as $propertyPath) {
            if ($hasConfig && !isset($allowedFields[$propertyPath])) {
                continue;
            }
            if ($hasConfig) {
                $fieldName = $allowedFields[$propertyPath];
                $field = $config->getField($fieldName);
                if ($field->isMetaProperty()) {
                    $metaPropertyMetadata = $this->entityMetadataFactory->createMetaPropertyMetadata(
                        $classMetadata,
                        $propertyPath,
                        $field->getDataType()
                    );
                    if ($propertyPath !== $fieldName) {
                        $metaPropertyMetadata->setName($fieldName);
                    }
                    $entityMetadata->addMetaProperty($metaPropertyMetadata);
                } else {
                    $fieldMetadata = $this->entityMetadataFactory->createFieldMetadata(
                        $classMetadata,
                        $propertyPath,
                        $field->getDataType()
                    );
                    if ($propertyPath !== $fieldName) {
                        $fieldMetadata->setName($fieldName);
                    }
                    $entityMetadata->addField($fieldMetadata);
                }
            } else {
                $entityMetadata->addField(
                    $this->entityMetadataFactory->createFieldMetadata($classMetadata, $propertyPath)
                );
            }
        }
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param ClassMetadata               $classMetadata
     * @param array                       $allowedFields
     * @param EntityDefinitionConfig|null $config
     */
    protected function loadAssociations(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        array $allowedFields,
        EntityDefinitionConfig $config = null
    ) {
        $hasConfig = null !== $config;
        $associations = $classMetadata->getAssociationNames();
        foreach ($associations as $propertyPath) {
            if ($hasConfig && !isset($allowedFields[$propertyPath])) {
                continue;
            }
            if ($hasConfig) {
                $associationName = $allowedFields[$propertyPath];
                $field = $config->getField($associationName);
                $associationMetadata = $this->entityMetadataFactory->createAssociationMetadata(
                    $classMetadata,
                    $propertyPath,
                    $field->getDataType()
                );
                if ($propertyPath !== $associationName) {
                    $associationMetadata->setName($associationName);
                }
            } else {
                $associationMetadata = $this->entityMetadataFactory->createAssociationMetadata(
                    $classMetadata,
                    $propertyPath
                );
            }
            $entityMetadata->addAssociation($associationMetadata);
        }
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param EntityDefinitionConfig $config
     */
    protected function loadPropertiesFromConfig(
        EntityMetadata $entityMetadata,
        EntityDefinitionConfig $config
    ) {
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }
            if ($field->isMetaProperty()) {
                if (!$entityMetadata->hasMetaProperty($fieldName)) {
                    $metaPropertyMetadata = new MetaPropertyMetadata($fieldName);
                    $metaPropertyMetadata->setDataType(
                        $this->assertDataType($field->getDataType(), $entityMetadata->getClassName(), $fieldName)
                    );
                    $entityMetadata->addMetaProperty($metaPropertyMetadata);
                }
            } else {
                $isField = $entityMetadata->hasField($fieldName);
                $isAssociation = !$isField && $entityMetadata->hasAssociation($fieldName);
                if (!$isField && !$isAssociation) {
                    $targetClass = $field->getTargetClass();
                    if (!$targetClass) {
                        $fieldMetadata = new FieldMetadata($fieldName);
                        $fieldMetadata->setDataType(
                            $this->assertDataType($field->getDataType(), $entityMetadata->getClassName(), $fieldName)
                        );
                        $fieldMetadata->setIsNullable(true);
                        $entityMetadata->addField($fieldMetadata);
                    } else {
                        $associationMetadata = new AssociationMetadata($fieldName);
                        $associationMetadata->setDataType(
                            $this->assertDataType($field->getDataType(), $entityMetadata->getClassName(), $fieldName)
                        );
                        $associationMetadata->setIsNullable(true);
                        $associationMetadata->setTargetClassName($targetClass);
                        $associationMetadata->addAcceptableTargetClassName($targetClass);
                        $associationMetadata->setIsCollection((bool)$field->isCollectionValuedAssociation());
                        $entityMetadata->addAssociation($associationMetadata);
                    }
                }
            }
        }
    }

    /**
     * @param mixed  $dataType
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function assertDataType($dataType, $entityClass, $fieldName)
    {
        if (!$dataType) {
            throw new RuntimeException(
                sprintf(
                    'The "%s" configuration attribute should be specified for the "%s" field of the "%s" entity.',
                    EntityDefinitionFieldConfig::DATA_TYPE,
                    $fieldName,
                    $entityClass
                )
            );
        }

        return $dataType;
    }

    /**
     * @param EntityDefinitionConfig $definition
     *
     * @return array [property path => field name, ...]
     */
    protected function getAllowedFields(EntityDefinitionConfig $definition)
    {
        $result = [];
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field->isExcluded()) {
                $propertyPath = $field->getPropertyPath() ?: $fieldName;
                $result[$propertyPath] = $fieldName;
            }
        }

        return $result;
    }

    /**
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $config
     *
     * @return EntityMetadata
     */
    protected function loadObjectMetadata($entityClass, EntityDefinitionConfig $config)
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $idFieldNames = $config->getIdentifierFieldNames();
        $entityMetadata->setIdentifierFieldNames($idFieldNames);
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }
            $targetClass = $field->getTargetClass();
            if (!$targetClass) {
                if ($field->isMetaProperty()) {
                    $metaPropertyMetadata = $entityMetadata->addMetaProperty(new MetaPropertyMetadata($fieldName));
                    $metaPropertyMetadata->setDataType($field->getDataType());
                } else {
                    $fieldMetadata = $entityMetadata->addField(new FieldMetadata($fieldName));
                    $fieldMetadata->setDataType($field->getDataType());
                    $fieldMetadata->setIsNullable(!in_array($fieldName, $idFieldNames, true));
                }
            } else {
                $associationMetadata = $entityMetadata->addAssociation(new AssociationMetadata($fieldName));
                $associationMetadata->setIsCollection($field->isCollectionValuedAssociation());
                $associationMetadata->setTargetClassName($targetClass);
                $associationMetadata->addAcceptableTargetClassName($targetClass);
                $associationMetadata->setIsNullable(true);
                $targetEntity = $field->getTargetEntity();
                if ($targetEntity && !$field->getDataType()) {
                    $associationDataType = DataType::STRING;
                    $targetIdFieldNames = $targetEntity->getIdentifierFieldNames();
                    if (1 === count($targetIdFieldNames)) {
                        $targetIdField = $targetEntity->getField(reset($targetIdFieldNames));
                        if ($targetIdField) {
                            $associationDataType = $targetIdField->getDataType();
                        }
                    }
                    $associationMetadata->setDataType($associationDataType);
                }
            }
        }

        return $entityMetadata;
    }
}
