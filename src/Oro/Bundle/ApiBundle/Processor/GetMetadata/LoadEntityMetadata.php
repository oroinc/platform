<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Loads metadata for an entity.
 * This processor works with both ORM and not ORM entities.
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
        foreach ($fields as $fieldName) {
            if ($hasConfig && !isset($allowedFields[$fieldName])) {
                continue;
            }
            if ($hasConfig) {
                $configFieldName = $allowedFields[$fieldName];
                $field = $this->entityMetadataFactory->createFieldMetadata(
                    $classMetadata,
                    $fieldName,
                    $config->getField($configFieldName)->getDataType()
                );
                if ($fieldName !== $configFieldName) {
                    $field->setName($configFieldName);
                }
            } else {
                $field = $this->entityMetadataFactory->createFieldMetadata($classMetadata, $fieldName);
            }
            $entityMetadata->addField($field);
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
        foreach ($associations as $associationName) {
            if ($hasConfig && !isset($allowedFields[$associationName])) {
                continue;
            }
            $association = $this->entityMetadataFactory->createAssociationMetadata(
                $classMetadata,
                $associationName
            );
            if ($hasConfig) {
                $configFieldName = $allowedFields[$associationName];
                if ($associationName !== $configFieldName) {
                    $association->setName($configFieldName);
                }
            }
            $entityMetadata->addAssociation($association);
        }
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
                $fieldMetadata = $entityMetadata->addField(new FieldMetadata($fieldName));
                $fieldMetadata->setDataType($field->getDataType());
                $fieldMetadata->setIsNullable(!in_array($fieldName, $idFieldNames, true));
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
