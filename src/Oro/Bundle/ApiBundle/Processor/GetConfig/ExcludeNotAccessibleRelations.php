<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\Extra\DisabledAssociationsConfigExtra;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
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
 * For example if entity1 has a reference to to entity2, but entity2 does not have API resource,
 * the relation will be excluded.
 */
class ExcludeNotAccessibleRelations implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private ResourcesProvider $resourcesProvider;
    private EntityOverrideProviderRegistry $entityOverrideProviderRegistry;

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
    public function process(ContextInterface $context): void
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

        $this->updateRelations(
            $definition,
            $entityClass,
            $context->getTargetAction(),
            $context->getVersion(),
            $context->getRequestType(),
            $context->hasExtra(DisabledAssociationsConfigExtra::NAME)
        );
    }

    private function updateRelations(
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $action,
        string $version,
        RequestType $requestType,
        bool $allowDisabledAssociations
    ): void {
        $entityOverrideProvider = $this->entityOverrideProviderRegistry->getEntityOverrideProvider($requestType);
        /** @var ClassMetadata $metadata */
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            // skip a field if it is already excluded or the "exclude" flag is set explicitly
            if ($field->isExcluded() || $field->hasExcluded()) {
                continue;
            }

            $targetClass = $field->getTargetClass();
            $targetMetadata = null;
            if (!$targetClass) {
                $targetClass = $this->getAssociationTargetClass($fieldName, $field, $metadata);
                if ($targetClass) {
                    $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass);
                }
            }
            if (!$targetClass || is_a($targetClass, EntityIdentifier::class, true)) {
                continue;
            }

            if (!$this->isResourceForRelatedEntityAvailable(
                $field,
                $targetClass,
                $targetMetadata,
                $action,
                $version,
                $requestType,
                $allowDisabledAssociations,
                $entityOverrideProvider
            )) {
                $field->setExcluded();
            }
        }
    }

    private function getAssociationTargetClass(
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        ClassMetadata $metadata
    ): ?string {
        $propertyPath = $field->getPropertyPath($fieldName);

        return $metadata->hasAssociation($propertyPath)
            ? $metadata->getAssociationMapping($propertyPath)['targetEntity']
            : null;
    }

    private function isResourceForRelatedEntityAvailable(
        EntityDefinitionFieldConfig $field,
        string $targetClass,
        ?ClassMetadata $targetMetadata,
        string $action,
        string $version,
        RequestType $requestType,
        bool $allowDisabledAssociations,
        EntityOverrideProviderInterface $entityOverrideProvider
    ): bool {
        if (DataType::isAssociationAsField($field->getDataType())) {
            return $this->isResourceForRelatedEntityKnown(
                $targetClass,
                $targetMetadata,
                $version,
                $requestType,
                $entityOverrideProvider
            );
        }

        return $this->isResourceForRelatedEntityAccessible(
            $targetClass,
            $targetMetadata,
            $action,
            $version,
            $requestType,
            $allowDisabledAssociations,
            $entityOverrideProvider
        );
    }

    private function isResourceForRelatedEntityKnown(
        string $targetClass,
        ?ClassMetadata $targetMetadata,
        string $version,
        RequestType $requestType,
        EntityOverrideProviderInterface $entityOverrideProvider
    ): bool {
        $targetClass = $this->resolveEntityClass($targetClass, $entityOverrideProvider);
        if ($this->resourcesProvider->isResourceKnown($targetClass, $version, $requestType)) {
            return true;
        }
        if (null !== $targetMetadata && !$targetMetadata->isInheritanceTypeNone()) {
            // check that at least one inherited entity has API resource
            foreach ($targetMetadata->subClasses as $inheritedEntityClass) {
                $inheritedEntityClass = $this->resolveEntityClass($inheritedEntityClass, $entityOverrideProvider);
                if ($this->resourcesProvider->isResourceKnown($inheritedEntityClass, $version, $requestType)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isResourceForRelatedEntityAccessible(
        string $targetClass,
        ?ClassMetadata $targetMetadata,
        string $action,
        string $version,
        RequestType $requestType,
        bool $allowDisabledAssociations,
        EntityOverrideProviderInterface $entityOverrideProvider
    ): bool {
        $targetClass = $this->resolveEntityClass($targetClass, $entityOverrideProvider);
        if ($this->isResourceAccessibleAsAssociation(
            $targetClass,
            $action,
            $version,
            $requestType,
            $allowDisabledAssociations
        )) {
            return true;
        }
        if (null !== $targetMetadata && !$targetMetadata->isInheritanceTypeNone()) {
            // check that at least one inherited entity has API resource
            foreach ($targetMetadata->subClasses as $subClass) {
                $subClass = $this->resolveEntityClass($subClass, $entityOverrideProvider);
                if ($this->isResourceAccessibleAsAssociation(
                    $subClass,
                    $action,
                    $version,
                    $requestType,
                    $allowDisabledAssociations
                )) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isResourceAccessibleAsAssociation(
        string $targetClass,
        string $action,
        string $version,
        RequestType $requestType,
        bool $allowDisabledAssociations
    ): bool {
        if ($allowDisabledAssociations) {
            return $this->resourcesProvider->isResourceAccessibleAsAssociation($targetClass, $version, $requestType);
        }

        return
            $this->resourcesProvider->isResourceEnabled($targetClass, $action, $version, $requestType)
            && $this->resourcesProvider->isResourceAccessibleAsAssociation($targetClass, $version, $requestType);
    }

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
