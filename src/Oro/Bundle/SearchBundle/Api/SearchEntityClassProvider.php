<?php

namespace Oro\Bundle\SearchBundle\Api;

use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;

/**
 * Provides class names for entities that can be searched via API.
 */
class SearchEntityClassProvider implements SearchEntityClassProviderInterface
{
    public function __construct(
        private readonly SearchIndexer $searchIndexer,
        private readonly ResourcesProvider $resourcesProvider
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessibleEntityClasses(string $version, RequestType $requestType): array
    {
        $entityClasses = $this->searchIndexer->getEntitiesListAliases();
        foreach ($entityClasses as $entityClass => $searchAlias) {
            if (!$this->resourcesProvider->isResourceAccessible($entityClass, $version, $requestType)) {
                unset($entityClasses[$entityClass]);
            }
        }

        return $entityClasses;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllowedEntityClasses(string $version, RequestType $requestType): array
    {
        $entityClasses = $this->getAccessibleEntityClasses($version, $requestType);
        $allowedSearchableEntityClasses = $this->searchIndexer->getAllowedEntitiesListAliases();
        foreach ($entityClasses as $entityClass => $searchAlias) {
            if (!isset($allowedSearchableEntityClasses[$entityClass])
                || !$this->resourcesProvider->isResourceEnabled($entityClass, ApiAction::GET, $version, $requestType)
            ) {
                unset($entityClasses[$entityClass]);
            }
        }

        return $entityClasses;
    }
}
