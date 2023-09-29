<?php

namespace Oro\Bundle\ActivityBundle\Api;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\SearchBundle\Api\SearchEntityClassProviderInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer;

/**
 * Provides class names for entities that can be returned by activity context related API resources.
 */
class ActivityContextEntityClassProvider implements SearchEntityClassProviderInterface
{
    private Indexer $searchIndexer;
    private ResourcesProvider $resourcesProvider;
    private ActivityManager $activityManager;
    private string $activityEntityClass;

    public function __construct(
        Indexer $searchIndexer,
        ResourcesProvider $resourcesProvider,
        ActivityManager $activityManager,
        string $activityEntityClass
    ) {
        $this->searchIndexer = $searchIndexer;
        $this->resourcesProvider = $resourcesProvider;
        $this->activityManager = $activityManager;
        $this->activityEntityClass = $activityEntityClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessibleEntityClasses(string $version, RequestType $requestType): array
    {
        $activityTargets = $this->activityManager->getActivityTargets($this->activityEntityClass);
        if (!$activityTargets) {
            return [];
        }

        $result = [];
        $searchableEntityClasses = $this->searchIndexer->getEntitiesListAliases();
        foreach ($activityTargets as $entityClass => $fieldName) {
            $searchAlias = $searchableEntityClasses[$entityClass] ?? null;
            if ($searchAlias
                && $this->resourcesProvider->isResourceAccessible($entityClass, $version, $requestType)
            ) {
                $result[$entityClass] = $searchAlias;
            }
        }

        return $result;
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
