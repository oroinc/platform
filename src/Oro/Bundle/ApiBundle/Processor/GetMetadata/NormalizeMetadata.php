<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Removes excluded fields and associations.
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
        $withExcludedProperties = $context->getWithExcludedProperties();
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$withExcludedProperties && $field->isExcluded()) {
                $entityMetadata->removeProperty($fieldName);
            } elseif ($processLinkedProperties) {
                $propertyPath = $field->getPropertyPath();
                if ($propertyPath && $fieldName !== $propertyPath) {
                    $path = ConfigUtil::explodePropertyPath($field->getPropertyPath());
                    if (count($path) > 0 && !$entityMetadata->hasProperty($fieldName)) {
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
                $associationMetadata->setPropertyPath($linkedProperty);
                $linkedPropertyPath = array_merge($propertyPath, [$linkedProperty]);
                $associationMetadata->setTargetMetadata(
                    $this->getMetadata(
                        $associationMetadata->getTargetClassName(),
                        $this->getTargetConfig($config, $propertyName, $linkedPropertyPath),
                        $context
                    )
                );
                $targetFieldConfig = $config->findFieldByPath($linkedPropertyPath, true);
                if (null !== $targetFieldConfig) {
                    $associationMetadata->setCollapsed($targetFieldConfig->isCollapsed());
                    if ($targetFieldConfig->getDataType()) {
                        $associationMetadata->setDataType($targetFieldConfig->getDataType());
                    }
                }
                $entityMetadata->addAssociation($associationMetadata);
                $addedPropertyName = $linkedProperty;
            } elseif ($classMetadata->hasField($linkedProperty)) {
                $fieldMetadata = $this->entityMetadataFactory->createFieldMetadata(
                    $classMetadata,
                    $linkedProperty
                );
                $fieldMetadata->setName($propertyName);
                $fieldMetadata->setPropertyPath($linkedProperty);
                $entityMetadata->addField($fieldMetadata);
                $addedPropertyName = $linkedProperty;
            } else {
                $targetEntityConfig = $config->getField($propertyPath[0])->getTargetEntity();
                $targetEntityConfig->getField($linkedProperty)->setExcluded(false);
                $targetEntityMetadata = $this->getMetadata(
                    $classMetadata->name,
                    $targetEntityConfig,
                    $context
                );
                $targetEntityConfig->getField($linkedProperty)->setExcluded(true);
                if ($targetEntityMetadata->hasAssociation($linkedProperty)) {
                    $association = clone $targetEntityMetadata->getAssociation($linkedProperty);
                    $association->setName($propertyName);
                    $entityMetadata->addAssociation($association);
                    $addedPropertyName = $propertyName;
                } elseif ($targetEntityMetadata->hasField($linkedProperty)) {
                    $field = clone $targetEntityMetadata->getField($linkedProperty);
                    $field->setName($propertyName);
                    $entityMetadata->addField($field);
                    $addedPropertyName = $propertyName;
                }
            }
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
            $targetField = $config->findFieldByPath($propertyPath, true);
            if (null === $targetField) {
                throw new RuntimeException(
                    sprintf(
                        'A configuration of "%s" field does not exist.',
                        implode(ConfigUtil::PATH_DELIMITER, $propertyPath)
                    )
                );
            }
            $targetConfig = $targetField->getTargetEntity();
            if (null === $targetConfig) {
                throw new RuntimeException(
                    sprintf(
                        'A configuration of the target entity for "%s" field does not exist.',
                        implode(ConfigUtil::PATH_DELIMITER, $propertyPath)
                    )
                );
            }
        }

        return $targetConfig;
    }
}
