<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Removes excluded fields and associations.
 * Renames fields and associations if their names are not correspond the configuration of entity
 * and there is the "property_path" attribute for the field.
 * For example, if the metadata has the "address_name" field, and there is the following configuration:
 *  address: { property_path: address_name }
 * the metadata field will be renamed to "address".
 * Expands metadata of root entity adding fields which are aliases for child associations.
 * For example, if there is the field configuration like:
 *  addressName: { property_path: address.name }
 * the "addressName" field should be added to the metadata.
 * The metadata of this field should be based on metadata of the "name" field of the "address" association.
 * By performance reasons all these actions are done in one processor.
 */
class NormalizeMetadata implements ProcessorInterface
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

        $entityMetadata = $context->getResult();
        if (null === $entityMetadata) {
            // metadata is not loaded
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // a configuration does not exist
            return;
        }

        $this->normalizeMetadata(
            $entityMetadata,
            $config,
            $this->doctrineHelper->isManageableEntityClass($context->getClassName())
        );
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param EntityDefinitionConfig $config
     * @param bool                   $processLinkedProperties
     */
    protected function normalizeMetadata(
        EntityMetadata $entityMetadata,
        EntityDefinitionConfig $config,
        $processLinkedProperties
    ) {
        $linkedPropertyNames = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                $entityMetadata->removeProperty($fieldName);
            } else {
                $propertyPath = $field->getPropertyPath();
                if ($propertyPath && $fieldName !== $propertyPath) {
                    $path = ConfigUtil::explodePropertyPath($field->getPropertyPath());
                    $pathCount = count($path);
                    if (1 === $pathCount) {
                        $entityMetadata->renameProperty($propertyPath, $fieldName);
                    } elseif ($processLinkedProperties
                        && $pathCount > 1
                        && !$entityMetadata->hasProperty($fieldName)
                    ) {
                        $addedPropertyName = $this->processLinkedProperty($entityMetadata, $fieldName, $path);
                        if ($addedPropertyName) {
                            $linkedPropertyNames[] = $addedPropertyName;
                        }
                    }
                }
            }
        }

        if ($config->isExcludeAll()) {
            $toRemoveFieldNames = array_diff(
                array_merge(array_keys($entityMetadata->getFields()), array_keys($entityMetadata->getAssociations())),
                array_merge($linkedPropertyNames, array_keys($fields))
            );
            foreach ($toRemoveFieldNames as $fieldName) {
                $entityMetadata->removeProperty($fieldName);
            }
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param string         $propertyName
     * @param string[]       $propertyPath
     *
     * @return string|null
     */
    protected function processLinkedProperty(EntityMetadata $entityMetadata, $propertyName, array $propertyPath)
    {
        $addedPropertyName = null;

        $linkedProperty = array_pop($propertyPath);
        $classMetadata = $this->doctrineHelper->findEntityMetadataByPath(
            $entityMetadata->getClassName(),
            $propertyPath
        );
        if (null !== $classMetadata) {
            if ($classMetadata->hasAssociation($linkedProperty)) {
                $associationMetadata = $this->entityMetadataFactory->createAssociationMetadata(
                    $classMetadata,
                    $linkedProperty
                );
                $associationMetadata->setName($propertyName);
                $entityMetadata->addAssociation($associationMetadata);
            } else {
                $fieldMetadata = $this->entityMetadataFactory->createFieldMetadata(
                    $classMetadata,
                    $linkedProperty
                );
                $fieldMetadata->setName($propertyName);
                $entityMetadata->addField($fieldMetadata);
            }
            $addedPropertyName = $linkedProperty;
        }

        return $addedPropertyName;
    }
}
