<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
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

    /** @var MetadataProvider */
    protected $metadataProvider;

    /**
     * @param DoctrineHelper        $doctrineHelper
     * @param EntityMetadataFactory $entityMetadataFactory
     * @param MetadataProvider      $metadataProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityMetadataFactory $entityMetadataFactory,
        MetadataProvider $metadataProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityMetadataFactory = $entityMetadataFactory;
        $this->metadataProvider = $metadataProvider;
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

        $this->normalizeMetadata(
            $entityMetadata,
            $context->getConfig(),
            $this->doctrineHelper->isManageableEntityClass($context->getClassName()),
            $context
        );
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param EntityDefinitionConfig $config
     * @param bool                   $processLinkedProperties
     * @param MetadataContext        $context
     */
    protected function normalizeMetadata(
        EntityMetadata $entityMetadata,
        EntityDefinitionConfig $config,
        $processLinkedProperties,
        MetadataContext $context
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
                        $addedPropertyName = $this->processLinkedProperty(
                            $entityMetadata,
                            $fieldName,
                            $path,
                            $config,
                            $context
                        );
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
     * @param EntityMetadata         $entityMetadata
     * @param string                 $propertyName
     * @param string[]               $propertyPath
     * @param EntityDefinitionConfig $config
     * @param MetadataContext        $context
     *
     * @return string|null
     */
    protected function processLinkedProperty(
        EntityMetadata $entityMetadata,
        $propertyName,
        array $propertyPath,
        EntityDefinitionConfig $config,
        MetadataContext $context
    ) {
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
                $associationMetadata->setTargetMetadata(
                    $this->getMetadata(
                        $associationMetadata->getTargetClassName(),
                        $this->getTargetConfig($config, $propertyName, array_merge($propertyPath, [$linkedProperty])),
                        $context
                    )
                );
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

    /**
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $config
     * @param MetadataContext        $context
     *
     * @return EntityMetadata
     */
    protected function getMetadata($entityClass, EntityDefinitionConfig $config, MetadataContext $context)
    {
        $targetMetadata = $this->metadataProvider->getMetadata(
            $entityClass,
            $context->getVersion(),
            $context->getRequestType(),
            $config,
            $context->getExtras()
        );
        if (null === $targetMetadata) {
            throw new RuntimeException(sprintf('A metadata for "%s" entity does not exist.', $entityClass));
        }

        return $targetMetadata;
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param string                 $propertyName
     * @param string[]               $propertyPath
     *
     * @return EntityDefinitionConfig
     *
     * @throws RuntimeException if a configuration of the target entity does not exist
     */
    protected function getTargetConfig(EntityDefinitionConfig $config, $propertyName, array $propertyPath)
    {
        $targetConfig = $config->getField($propertyName)->getTargetEntity();
        if (null === $targetConfig) {
            $targetField = $this->findFieldByPropertyPath($config, $propertyPath);
            if (null === $targetField) {
                throw new RuntimeException(
                    sprintf(
                        'A configuration of "%s" field does not exist.',
                        implode('.', $propertyPath)
                    )
                );
            }
            $targetConfig = $targetField->getTargetEntity();
            if (null === $targetConfig) {
                throw new RuntimeException(
                    sprintf(
                        'A configuration of the target entity for "%s" field does not exist.',
                        implode('.', $propertyPath)
                    )
                );
            }
        }

        return $targetConfig;
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param string[]               $propertyPath
     *
     * @return EntityDefinitionFieldConfig|null
     */
    protected function findFieldByPropertyPath(EntityDefinitionConfig $config, array $propertyPath)
    {
        $pathCount = count($propertyPath);

        $targetConfig = $config;
        for ($i = 0; $i < $pathCount - 1; $i++) {
            $fieldConfig = $targetConfig->findField($propertyPath[$i], true);
            if (null === $fieldConfig) {
                return null;
            }
            $targetConfig = $fieldConfig->getTargetEntity();
            if (null === $targetConfig) {
                return null;
            }
        }

        return $targetConfig->findField($propertyPath[$pathCount - 1], true);
    }
}
