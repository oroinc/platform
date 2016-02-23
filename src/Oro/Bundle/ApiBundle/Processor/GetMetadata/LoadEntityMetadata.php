<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Adds metadata for manageable entity and its fields.
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
        $this->doctrineHelper        = $doctrineHelper;
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
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        // filter excluded fields on this stage though there is another processor doing the same
        // it is done due to performance reasons
        $config        = $context->getConfig();
        $allowedFields = null !== $config
            ? $this->getAllowedFields($config)
            : [];

        $classMetadata  = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $entityMetadata = $this->entityMetadataFactory->createEntityMetadata($classMetadata);

        $this->loadFields($entityMetadata, $classMetadata, $allowedFields, null !== $config);
        $this->loadAssociations($entityMetadata, $classMetadata, $allowedFields, null !== $config);

        $context->setResult($entityMetadata);
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param ClassMetadata  $classMetadata
     * @param array          $allowedFields
     * @param bool           $hasConfig
     */
    protected function loadFields(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        array $allowedFields,
        $hasConfig
    ) {
        $fields = $classMetadata->getFieldNames();
        foreach ($fields as $fieldName) {
            if ($hasConfig && !isset($allowedFields[$fieldName])) {
                continue;
            }
            $field = $this->entityMetadataFactory->createFieldMetadata($classMetadata, $fieldName);
            if ($hasConfig) {
                $configFieldName = $allowedFields[$fieldName];
                if ($fieldName !== $configFieldName) {
                    $field->setName($configFieldName);
                }
            }
            $entityMetadata->addField($field);
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param ClassMetadata  $classMetadata
     * @param array          $allowedFields
     * @param bool           $hasConfig
     */
    protected function loadAssociations(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        array $allowedFields,
        $hasConfig
    ) {
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
     * @return array
     */
    protected function getAllowedFields(EntityDefinitionConfig $definition)
    {
        $result = [];
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field->isExcluded()) {
                $propertyPath          = $field->getPropertyPath() ?: $fieldName;
                $result[$propertyPath] = $fieldName;
            }
        }

        return $result;
    }
}
