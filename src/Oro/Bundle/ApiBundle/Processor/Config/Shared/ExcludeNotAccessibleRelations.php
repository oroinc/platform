<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Excludes relations that are pointed to not accessible resources.
 * For example if entity1 has a reference to to entity2, but entity2 does not have Data API resource,
 * the relation will be excluded.
 */
class ExcludeNotAccessibleRelations implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    /** @var EntityOverrideProviderRegistry */
    private $entityOverrideProviderRegistry;

    /**
     * @param DoctrineHelper                 $doctrineHelper
     * @param ResourcesProvider              $resourcesProvider
     * @param EntityOverrideProviderRegistry $entityOverrideProviderRegistry
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ResourcesProvider $resourcesProvider,
        EntityOverrideProviderRegistry $entityOverrideProviderRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->resourcesProvider = $resourcesProvider;
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $this->updateRelations($definition, $entityClass, $context->getVersion(), $context->getRequestType());
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string                 $version
     * @param RequestType            $requestType
     */
    private function updateRelations(
        EntityDefinitionConfig $definition,
        $entityClass,
        $version,
        RequestType $requestType
    ) {
        $entityOverrideProvider = $this->entityOverrideProviderRegistry->getEntityOverrideProvider($requestType);
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            // skip a field if it is already excluded or the "exclude" flag is set explicitly
            if ($field->isExcluded() || $field->hasExcluded()) {
                continue;
            }

            $propertyPath = $field->getPropertyPath($fieldName);
            if (!$metadata->hasAssociation($propertyPath)) {
                continue;
            }

            $mapping = $metadata->getAssociationMapping($propertyPath);
            $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($mapping['targetEntity']);
            if (!$this->isResourceForRelatedEntityAvailable(
                $field,
                $targetMetadata,
                $version,
                $requestType,
                $entityOverrideProvider
            )) {
                $field->setExcluded();
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig     $field
     * @param ClassMetadata                   $targetMetadata
     * @param string                          $version
     * @param RequestType                     $requestType
     * @param EntityOverrideProviderInterface $entityOverrideProvider
     *
     * @return bool
     */
    private function isResourceForRelatedEntityAvailable(
        EntityDefinitionFieldConfig $field,
        ClassMetadata $targetMetadata,
        string $version,
        RequestType $requestType,
        EntityOverrideProviderInterface $entityOverrideProvider
    ): bool {
        if (DataType::isAssociationAsField($field->getDataType())) {
            return $this->isResourceForRelatedEntityKnown(
                $targetMetadata,
                $version,
                $requestType,
                $entityOverrideProvider
            );
        }

        return $this->isResourceForRelatedEntityAccessible(
            $targetMetadata,
            $version,
            $requestType,
            $entityOverrideProvider
        );
    }

    /**
     * @param ClassMetadata                   $targetMetadata
     * @param string                          $version
     * @param RequestType                     $requestType
     * @param EntityOverrideProviderInterface $entityOverrideProvider
     *
     * @return bool
     */
    private function isResourceForRelatedEntityKnown(
        ClassMetadata $targetMetadata,
        string $version,
        RequestType $requestType,
        EntityOverrideProviderInterface $entityOverrideProvider
    ): bool {
        $targetClass = $this->resolveEntityClass($targetMetadata->name, $entityOverrideProvider);
        if ($this->resourcesProvider->isResourceKnown($targetClass, $version, $requestType)) {
            return true;
        }
        if ($targetMetadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
            // check that at least one inherited entity has Data API resource
            foreach ($targetMetadata->subClasses as $inheritedEntityClass) {
                $inheritedEntityClass = $this->resolveEntityClass($inheritedEntityClass, $entityOverrideProvider);
                if ($this->resourcesProvider->isResourceKnown($inheritedEntityClass, $version, $requestType)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param ClassMetadata                   $targetMetadata
     * @param string                          $version
     * @param RequestType                     $requestType
     * @param EntityOverrideProviderInterface $entityOverrideProvider
     *
     * @return bool
     */
    private function isResourceForRelatedEntityAccessible(
        ClassMetadata $targetMetadata,
        string $version,
        RequestType $requestType,
        EntityOverrideProviderInterface $entityOverrideProvider
    ): bool {
        $targetClass = $this->resolveEntityClass($targetMetadata->name, $entityOverrideProvider);
        if ($this->resourcesProvider->isResourceAccessible($targetClass, $version, $requestType)) {
            return true;
        }
        if ($targetMetadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
            // check that at least one inherited entity has Data API resource
            foreach ($targetMetadata->subClasses as $inheritedEntityClass) {
                $inheritedEntityClass = $this->resolveEntityClass($inheritedEntityClass, $entityOverrideProvider);
                if ($this->resourcesProvider->isResourceAccessible($inheritedEntityClass, $version, $requestType)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string                          $entityClass
     * @param EntityOverrideProviderInterface $entityOverrideProvider
     *
     * @return string
     */
    private function resolveEntityClass(
        string $entityClass,
        EntityOverrideProviderInterface $entityOverrideProvider
    ): string {
        $substituteEntityClass = $entityOverrideProvider->getSubstituteEntityClass($entityClass);
        if ($substituteEntityClass) {
            return $substituteEntityClass;
        }

        return $entityClass;
    }
}
