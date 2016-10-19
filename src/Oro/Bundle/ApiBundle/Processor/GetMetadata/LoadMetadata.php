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
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Loads metadata for an entity.
 * This processor works with both ORM and not ORM entities.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class LoadMetadata implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityMetadataFactory */
    protected $entityMetadataFactory;

    /** @var MetadataProvider */
    protected $metadataProvider;

    /** @var AssociationManager */
    protected $associationManager;

    /**
     * @param DoctrineHelper        $doctrineHelper
     * @param EntityMetadataFactory $entityMetadataFactory
     * @param MetadataProvider      $metadataProvider
     * @param AssociationManager    $associationManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityMetadataFactory $entityMetadataFactory,
        MetadataProvider $metadataProvider,
        AssociationManager $associationManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityMetadataFactory = $entityMetadataFactory;
        $this->metadataProvider = $metadataProvider;
        $this->associationManager = $associationManager;
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
            $entityMetadata = $this->loadEntityMetadata(
                $entityClass,
                $config,
                $context->getWithExcludedProperties(),
                $context->getTargetAction()
            );
            $this->completeAssociationMetadata($entityMetadata, $config, $context);
            $context->setResult($entityMetadata);
        } elseif ($config->hasFields()) {
            $entityMetadata = $this->loadObjectMetadata(
                $entityClass,
                $config,
                $context->getWithExcludedProperties(),
                $context->getTargetAction()
            );
            $this->completeAssociationMetadata($entityMetadata, $config, $context);
            $context->setResult($entityMetadata);
        }
    }

    /**
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $config
     * @param bool                   $withExcludedProperties
     * @param string                 $targetAction
     *
     * @return EntityMetadata
     */
    protected function loadEntityMetadata(
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
        $this->loadFields($entityMetadata, $classMetadata, $allowedFields, $config, $targetAction);
        $this->loadAssociations($entityMetadata, $classMetadata, $allowedFields, $config, $targetAction);
        $this->loadPropertiesFromConfig($entityMetadata, $config, $withExcludedProperties, $targetAction);

        return $entityMetadata;
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
    protected function loadFields(
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
                $metaPropertyMetadata = $this->entityMetadataFactory->createMetaPropertyMetadata(
                    $classMetadata,
                    $propertyPath,
                    $field->getDataType()
                );
                if ($propertyPath !== $fieldName) {
                    $metaPropertyMetadata->setName($fieldName);
                }
                $this->setPropertyPath($metaPropertyMetadata, $fieldName, $field, $targetAction);
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
                $this->setPropertyPath($fieldMetadata, $fieldName, $field, $targetAction);
                $entityMetadata->addField($fieldMetadata);
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
    protected function loadAssociations(
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
            $field = $config->getField($associationName);
            $associationMetadata = $this->entityMetadataFactory->createAssociationMetadata(
                $classMetadata,
                $propertyPath,
                $field->getDataType()
            );
            if ($propertyPath !== $associationName) {
                $associationMetadata->setName($associationName);
            }
            $this->setPropertyPath($associationMetadata, $associationName, $field, $targetAction);
            $associationMetadata->setCollapsed($field->isCollapsed());
            $entityMetadata->addAssociation($associationMetadata);
        }
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param EntityDefinitionConfig $config
     * @param bool                   $withExcludedProperties
     * @param string                 $targetAction
     */
    protected function loadPropertiesFromConfig(
        EntityMetadata $entityMetadata,
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
                        $this->addAssociationMetadata(
                            $entityMetadata,
                            $entityClass,
                            $fieldName,
                            $field,
                            $targetAction
                        );
                    } else {
                        $this->addFieldMetadata(
                            $entityMetadata,
                            $entityClass,
                            $fieldName,
                            $field,
                            $targetAction
                        );
                    }
                }
            } elseif (!$entityMetadata->hasMetaProperty($fieldName)) {
                $this->addMetaPropertyMetadata(
                    $entityMetadata,
                    $entityClass,
                    $fieldName,
                    $field,
                    $targetAction
                );
            }
        }
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param string                      $entityClass
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     */
    protected function addMetaPropertyMetadata(
        EntityMetadata $entityMetadata,
        $entityClass,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $metaPropertyMetadata = $entityMetadata->addMetaProperty(new MetaPropertyMetadata($fieldName));
        $this->setPropertyPath($metaPropertyMetadata, $fieldName, $field, $targetAction);
        $metaPropertyMetadata->setDataType(
            $this->assertDataType($field->getDataType(), $entityClass, $fieldName)
        );
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param string                      $entityClass
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     */
    protected function addFieldMetadata(
        EntityMetadata $entityMetadata,
        $entityClass,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $fieldMetadata = $entityMetadata->addField(new FieldMetadata($fieldName));
        $this->setPropertyPath($fieldMetadata, $fieldName, $field, $targetAction);
        $fieldMetadata->setDataType(
            $this->assertDataType($field->getDataType(), $entityClass, $fieldName)
        );
        $fieldMetadata->setIsNullable(true);
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param string                      $entityClass
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     */
    protected function addAssociationMetadata(
        EntityMetadata $entityMetadata,
        $entityClass,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $targetClass = $field->getTargetClass();
        $dataType = $this->assertDataType($field->getDataType(), $entityClass, $fieldName);
        $associationMetadata = $entityMetadata->addAssociation(new AssociationMetadata($fieldName));
        $this->setPropertyPath($associationMetadata, $fieldName, $field, $targetAction);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setIsNullable(true);
        $associationMetadata->setCollapsed($field->isCollapsed());
        if (DataType::isExtendedAssociation($dataType)) {
            list($associationType, $associationKind) = DataType::parseExtendedAssociation($dataType);
            $targets = $this->getExtendedAssociationTargets($entityClass, $associationType, $associationKind);
            $this->setAssociationDataType($associationMetadata, $field);
            $associationMetadata->setAssociationType($associationType);
            $associationMetadata->setAcceptableTargetClassNames(array_keys($targets));
            $associationMetadata->setIsCollection((bool)$field->isCollectionValuedAssociation());
        } else {
            $associationMetadata->setDataType($dataType);
            $this->setAssociationType($associationMetadata, $field->isCollectionValuedAssociation());
            $associationMetadata->addAcceptableTargetClassName($targetClass);
        }
    }

    /**
     * @param PropertyMetadata            $propertyMetadata
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     */
    protected function setPropertyPath(
        PropertyMetadata $propertyMetadata,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $propertyPath = null;
        if (in_array($targetAction, [ApiActions::CREATE, ApiActions::UPDATE], true)) {
            $formOptions = $field->getFormOptions();
            if (!empty($formOptions) && array_key_exists('property_path', $formOptions)) {
                $propertyPath = $formOptions['property_path'];
            }
        }
        if (!$propertyPath) {
            $propertyPath = $field->getPropertyPath($fieldName);
        }
        if ($propertyPath !== $fieldName) {
            $propertyMetadata->setPropertyPath($propertyPath);
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
     * @param string $entityClass
     * @param string $associationType
     * @param string $associationKind
     *
     * @return array [class name => field name, ...]
     */
    protected function getExtendedAssociationTargets($entityClass, $associationType, $associationKind)
    {
        $targets = $this->associationManager->getAssociationTargets(
            $entityClass,
            null,
            $associationType,
            $associationKind
        );

        return $targets;
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
                $result[$propertyPath] = $fieldName;
            }
        }

        return $result;
    }

    /**
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $config
     * @param bool                   $withExcludedProperties
     * @param string                 $targetAction
     *
     * @return EntityMetadata
     */
    protected function loadObjectMetadata(
        $entityClass,
        EntityDefinitionConfig $config,
        $withExcludedProperties,
        $targetAction
    ) {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $idFieldNames = $config->getIdentifierFieldNames();
        $entityMetadata->setIdentifierFieldNames($idFieldNames);
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$withExcludedProperties && $field->isExcluded()) {
                continue;
            }
            $targetClass = $field->getTargetClass();
            if ($targetClass) {
                $associationMetadata = $entityMetadata->addAssociation(new AssociationMetadata($fieldName));
                $this->setPropertyPath($associationMetadata, $fieldName, $field, $targetAction);
                if (!$field->getDataType()) {
                    $this->setAssociationDataType($associationMetadata, $field);
                } else {
                    $associationMetadata->setDataType($field->getDataType());
                }
                $this->setAssociationType($associationMetadata, $field->isCollectionValuedAssociation());
                $associationMetadata->setIsNullable(true);
                $associationMetadata->setCollapsed($field->isCollapsed());
                $associationMetadata->setTargetClassName($targetClass);
                $associationMetadata->addAcceptableTargetClassName($targetClass);
            } elseif ($field->isMetaProperty()) {
                $metaPropertyMetadata = $entityMetadata->addMetaProperty(new MetaPropertyMetadata($fieldName));
                $this->setPropertyPath($metaPropertyMetadata, $fieldName, $field, $targetAction);
                $metaPropertyMetadata->setDataType($field->getDataType());
                if (ConfigUtil::CLASS_NAME === $fieldName) {
                    $entityMetadata->setInheritedType(true);
                }
            } else {
                $fieldMetadata = $entityMetadata->addField(new FieldMetadata($fieldName));
                $this->setPropertyPath($fieldMetadata, $fieldName, $field, $targetAction);
                $fieldMetadata->setDataType($field->getDataType());
                $fieldMetadata->setIsNullable(!in_array($fieldName, $idFieldNames, true));
            }
        }

        return $entityMetadata;
    }

    /**
     * @param AssociationMetadata         $associationMetadata
     * @param EntityDefinitionFieldConfig $field
     */
    protected function setAssociationDataType(
        AssociationMetadata $associationMetadata,
        EntityDefinitionFieldConfig $field
    ) {
        $targetEntity = $field->getTargetEntity();
        if ($targetEntity) {
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

    /**
     * @param AssociationMetadata $associationMetadata
     * @param bool                $isCollection
     */
    protected function setAssociationType(AssociationMetadata $associationMetadata, $isCollection)
    {
        if ($isCollection) {
            $associationMetadata->setAssociationType(RelationType::MANY_TO_MANY);
            $associationMetadata->setIsCollection(true);
        } else {
            $associationMetadata->setAssociationType(RelationType::MANY_TO_ONE);
            $associationMetadata->setIsCollection(false);
        }
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param EntityDefinitionConfig $config
     * @param MetadataContext        $context
     */
    protected function completeAssociationMetadata(
        EntityMetadata $entityMetadata,
        EntityDefinitionConfig $config,
        MetadataContext $context
    ) {
        $associations = $entityMetadata->getAssociations();
        foreach ($associations as $associationName => $association) {
            if (null !== $association->getTargetMetadata()) {
                // metadata for an associated entity is already loaded
                continue;
            }
            $field = $config->getField($associationName);
            if (null === $field || !$field->hasTargetEntity()) {
                // a configuration of an association fields does not exist
                continue;
            }

            $targetMetadata = $this->metadataProvider->getMetadata(
                $association->getTargetClassName(),
                $context->getVersion(),
                $context->getRequestType(),
                $field->getTargetEntity(),
                $context->getExtras()
            );
            if (null !== $targetMetadata) {
                $association->setTargetMetadata($targetMetadata);
            }
        }
    }
}
