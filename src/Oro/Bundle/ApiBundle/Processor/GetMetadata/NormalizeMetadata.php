<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes excluded fields and associations.
 * Expands metadata of root entity adding fields which are aliases for child associations.
 * For example, if there is the field configuration like:
 *  addressName: { property_path: address.name }
 * the "addressName" field should be added to the metadata.
 * The metadata of this field should be based on metadata of the "name" field of the "address" association.
 * Updates overridden entity class names in the acceptable target class names for associations
 * that has the target class name equal to "Oro\Bundle\ApiBundle\Model\EntityIdentifier".
 * By performance reasons all these actions are done in one processor.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class NormalizeMetadata implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private EntityMetadataFactory $entityMetadataFactory;
    private MetadataProvider $metadataProvider;
    private EntityOverrideProviderRegistry $entityOverrideProviderRegistry;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityMetadataFactory $entityMetadataFactory,
        MetadataProvider $metadataProvider,
        EntityOverrideProviderRegistry $entityOverrideProviderRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityMetadataFactory = $entityMetadataFactory;
        $this->metadataProvider = $metadataProvider;
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
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
        $this->normalizeAcceptableTargetClassNames(
            $entityMetadata,
            $this->entityOverrideProviderRegistry->getEntityOverrideProvider($context->getRequestType())
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function normalizeMetadata(
        EntityMetadata $entityMetadata,
        EntityDefinitionConfig $config,
        bool $processLinkedProperties,
        MetadataContext $context
    ): void {
        $resolvedPropertyNames = [];
        $withExcludedProperties = $context->getWithExcludedProperties();
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$withExcludedProperties && $field->isExcluded()) {
                $entityMetadata->removeProperty($fieldName);
            } elseif ($processLinkedProperties) {
                if ($entityMetadata->hasProperty($fieldName)) {
                    $resolvedPropertyNames[] = $fieldName;
                } else {
                    $propertyPath = $field->getPropertyPath();
                    if ($propertyPath && $fieldName !== $propertyPath) {
                        $isPropertyAdded = $this->processLinkedProperty(
                            $entityMetadata,
                            $fieldName,
                            $propertyPath,
                            $config,
                            $field,
                            $context
                        );
                        if ($isPropertyAdded) {
                            $resolvedPropertyNames[] = $fieldName;
                        }
                    }
                }
            } else {
                $resolvedPropertyNames[] = $fieldName;
            }
        }

        if ($config->isExcludeAll()) {
            $toRemoveFieldNames = array_diff(
                array_merge(
                    array_keys($entityMetadata->getFields()),
                    array_keys($entityMetadata->getAssociations())
                ),
                $resolvedPropertyNames
            );
            foreach ($toRemoveFieldNames as $fieldName) {
                $entityMetadata->removeProperty($fieldName);
            }
        }
    }

    private function normalizeAcceptableTargetClassNames(
        EntityMetadata $entityMetadata,
        EntityOverrideProviderInterface $entityOverrideProvider
    ): void {
        $associations = $entityMetadata->getAssociations();
        foreach ($associations as $association) {
            if (EntityIdentifier::class === $association->getTargetClassName()) {
                $acceptableTargetClassNames = $association->getAcceptableTargetClassNames();
                foreach ($acceptableTargetClassNames as $i => $acceptableClass) {
                    $substituteAcceptableClass = $entityOverrideProvider->getSubstituteEntityClass($acceptableClass);
                    if ($substituteAcceptableClass) {
                        $acceptableTargetClassNames[$i] = $substituteAcceptableClass;
                    }
                }
                $association->setAcceptableTargetClassNames($acceptableTargetClassNames);
            }
            $targetMetadata = $association->getTargetMetadata();
            if (null !== $targetMetadata) {
                $this->normalizeAcceptableTargetClassNames($targetMetadata, $entityOverrideProvider);
            }
        }
    }

    private function processLinkedProperty(
        EntityMetadata $entityMetadata,
        string $fieldName,
        string $propertyPath,
        EntityDefinitionConfig $config,
        EntityDefinitionFieldConfig $field,
        MetadataContext $context
    ): bool {
        $associationPath = ConfigUtil::explodePropertyPath($propertyPath);
        $linkedPropertyName = array_pop($associationPath);

        if (!empty($associationPath)) {
            $targetEntityMetadata = $this->getTargetEntityMetadata(
                $config,
                $associationPath,
                $linkedPropertyName,
                $context
            );
            if (null !== $targetEntityMetadata) {
                $result = $this->copyLinkedProperty(
                    $entityMetadata,
                    $linkedPropertyName,
                    $fieldName,
                    $targetEntityMetadata
                );
                if ($result && $field->hasDirection()) {
                    $this->setLinkedPropertyDirection($entityMetadata, $fieldName, $field);
                }

                return $result;
            }
        }

        $targetClassMetadata = $this->doctrineHelper->findEntityMetadataByPath(
            $entityMetadata->getClassName(),
            $associationPath
        );
        if (null === $targetClassMetadata) {
            return false;
        }

        $result = $this->addLinkedProperty(
            $entityMetadata,
            $linkedPropertyName,
            $fieldName,
            $propertyPath,
            $config,
            $targetClassMetadata,
            $context
        );
        if ($result && $field->hasDirection()) {
            $this->setLinkedPropertyDirection($entityMetadata, $fieldName, $field);
        }

        return $result;
    }

    private function copyLinkedProperty(
        EntityMetadata $entityMetadata,
        string $linkedPropertyName,
        string $fieldName,
        EntityMetadata $targetEntityMetadata
    ): bool {
        $isPropertyAdded = false;
        if ($targetEntityMetadata->hasAssociation($linkedPropertyName)) {
            $association = clone $targetEntityMetadata->getAssociation($linkedPropertyName);
            $association->setName($fieldName);
            $entityMetadata->addAssociation($association);
            $isPropertyAdded = true;
        } elseif ($targetEntityMetadata->hasField($linkedPropertyName)) {
            $field = clone $targetEntityMetadata->getField($linkedPropertyName);
            $field->setName($fieldName);
            $entityMetadata->addField($field);
            $isPropertyAdded = true;
        }

        return $isPropertyAdded;
    }

    private function addLinkedProperty(
        EntityMetadata $entityMetadata,
        string $linkedPropertyName,
        string $fieldName,
        string $propertyPath,
        EntityDefinitionConfig $config,
        ClassMetadata $targetClassMetadata,
        MetadataContext $context
    ): bool {
        $isPropertyAdded = false;
        if ($targetClassMetadata->hasAssociation($linkedPropertyName)) {
            $associationMetadata = $this->entityMetadataFactory->createAssociationMetadata(
                $targetClassMetadata,
                $linkedPropertyName
            );
            $associationMetadata->setName($fieldName);
            $associationMetadata->setPropertyPath($propertyPath);
            $associationMetadata->setTargetMetadata(
                $this->getMetadata(
                    $associationMetadata->getTargetClassName(),
                    $this->getTargetConfig($config, $fieldName, $propertyPath),
                    $context
                )
            );
            $targetFieldConfig = $config->findFieldByPath($propertyPath, true);
            if (null !== $targetFieldConfig) {
                $associationMetadata->setCollapsed($targetFieldConfig->isCollapsed());
                if ($targetFieldConfig->getDataType()) {
                    $associationMetadata->setDataType($targetFieldConfig->getDataType());
                }
            }
            $entityMetadata->addAssociation($associationMetadata);
            $isPropertyAdded = true;
        } elseif ($targetClassMetadata->hasField($linkedPropertyName)) {
            $fieldMetadata = $this->entityMetadataFactory->createFieldMetadata(
                $targetClassMetadata,
                $linkedPropertyName
            );
            $fieldMetadata->setName($fieldName);
            $fieldMetadata->setPropertyPath($propertyPath);
            $entityMetadata->addField($fieldMetadata);
            $isPropertyAdded = true;
        }

        return $isPropertyAdded;
    }

    private function setLinkedPropertyDirection(
        EntityMetadata $entityMetadata,
        string $fieldName,
        EntityDefinitionFieldConfig $field
    ): void {
        $entityMetadata->getProperty($fieldName)
            ->setDirection($field->isInput(), $field->isOutput());
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param string[]               $associationPath
     * @param string                 $linkedPropertyName
     * @param MetadataContext        $context
     *
     * @return EntityMetadata|null
     */
    private function getTargetEntityMetadata(
        EntityDefinitionConfig $config,
        array $associationPath,
        string $linkedPropertyName,
        MetadataContext $context
    ): ?EntityMetadata {
        $targetAssociation = $config->findFieldByPath($associationPath, true);
        if (null === $targetAssociation) {
            return null;
        }
        $targetEntityClass = $targetAssociation->getTargetClass();
        if (!$targetEntityClass) {
            return null;
        }
        $targetEntityConfig = $targetAssociation->getTargetEntity();
        if (null === $targetEntityConfig) {
            return null;
        }
        $targetField = $targetEntityConfig->findField($linkedPropertyName, true);
        if (null === $targetField) {
            return null;
        }

        $excluded = $targetField->hasExcluded()
            ? $targetField->isExcluded()
            : null;
        $targetField->setExcluded(false);
        try {
            $targetEntityMetadata = $this->getMetadata($targetEntityClass, $targetEntityConfig, $context);
        } finally {
            $targetField->setExcluded($excluded);
        }

        return $targetEntityMetadata;
    }

    private function getMetadata(
        string $entityClass,
        EntityDefinitionConfig $config,
        MetadataContext $context
    ): EntityMetadata {
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

    private function getTargetConfig(
        EntityDefinitionConfig $config,
        string $fieldName,
        string $propertyPath
    ): EntityDefinitionConfig {
        $targetField = $config->getField($fieldName);
        if (null === $targetField) {
            throw new RuntimeException(sprintf(
                'A configuration of "%s" field does not exist.',
                $fieldName
            ));
        }
        $targetConfig = $targetField->getTargetEntity();
        if (null === $targetConfig) {
            $targetField = $config->findFieldByPath($propertyPath, true);
            if (null === $targetField) {
                throw new RuntimeException(sprintf(
                    'A configuration of "%s" field does not exist.',
                    $propertyPath
                ));
            }
            $targetConfig = $targetField->getTargetEntity();
            if (null === $targetConfig) {
                throw new RuntimeException(sprintf(
                    'A configuration of the target entity for "%s" field does not exist.',
                    $propertyPath
                ));
            }
        }

        return $targetConfig;
    }
}
