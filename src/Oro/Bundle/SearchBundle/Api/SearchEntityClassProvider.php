<?php

namespace Oro\Bundle\SearchBundle\Api;

use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\SearchBundle\Engine\Indexer;

/**
 * Provides class names for entities that can be searched via API.
 */
class SearchEntityClassProvider implements SearchEntityClassProviderInterface
{
    private Indexer $searchIndexer;
    private ResourcesProvider $resourcesProvider;

    public function __construct(Indexer $searchIndexer, ResourcesProvider $resourcesProvider)
    {
        $this->searchIndexer = $searchIndexer;
        $this->resourcesProvider = $resourcesProvider;
    }

    #[\Override]
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

    #[\Override]
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
