<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

/**
 * Provides a set of methods to get information about extended associations.
 */
class ExtendedAssociationProvider
{
    private AssociationManager $associationManager;
    private ResourcesProvider $resourcesProvider;
    private EntityOverrideProviderRegistry $entityOverrideProviderRegistry;

    public function __construct(
        AssociationManager $associationManager,
        ResourcesProvider $resourcesProvider,
        EntityOverrideProviderRegistry $entityOverrideProviderRegistry
    ) {
        $this->associationManager = $associationManager;
        $this->resourcesProvider = $resourcesProvider;
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
    }

    /**
     * Gets the list of fields responsible to store extended associations accessible via API.
     *
     * @param string      $entityClass
     * @param string      $associationType
     * @param string|null $associationKind
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array [target entity class => target field name, ...]
     */
    public function getExtendedAssociationTargets(
        string $entityClass,
        string $associationType,
        ?string $associationKind,
        string $version,
        RequestType $requestType
    ): array {
        $entityOverrideProvider = $this->entityOverrideProviderRegistry->getEntityOverrideProvider($requestType);

        return $this->associationManager->getAssociationTargets(
            $entityClass,
            fn (string $ownerClass, string $targetClass) => $this->resourcesProvider->isResourceAccessible(
                $entityOverrideProvider->getSubstituteEntityClass($targetClass) ?: $targetClass,
                $version,
                $requestType
            ),
            $associationType,
            $associationKind
        );
    }
    /**
     * Gets the list of fields responsible to store extended associations filtered by the given target fields.
     *
     * @param string      $entityClass
     * @param string      $associationType
     * @param string|null $associationKind
     * @param string[]    $targetFieldNames
     *
     * @return array [target entity class => target field name, ...]
     */
    public function filterExtendedAssociationTargets(
        string $entityClass,
        string $associationType,
        ?string $associationKind,
        array $targetFieldNames
    ): array {
        if (!$targetFieldNames) {
            return [];
        }

        $associationTargets = $this->associationManager->getAssociationTargets(
            $entityClass,
            null,
            $associationType,
            $associationKind
        );

        $targetFieldNameMap = array_fill_keys($targetFieldNames, true);
        $filteredAssociationTargets = [];
        foreach ($associationTargets as $targetClass => $targetField) {
            if (isset($targetFieldNameMap[$targetField])) {
                $filteredAssociationTargets[$targetClass] = $targetField;
            }
        }

        return $filteredAssociationTargets;
    }
}
