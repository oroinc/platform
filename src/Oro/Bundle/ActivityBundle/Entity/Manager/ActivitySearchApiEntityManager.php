<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

/**
 * Provides API access to activities through full-text search capabilities.
 *
 * This manager enables searching for activities via the search engine, allowing clients
 * to query activities by text content and filter results by target entity types. It integrates
 * with the search indexer to perform efficient full-text searches and maps search results
 * to the appropriate activity types and their associated target entities.
 */
class ActivitySearchApiEntityManager extends ApiEntityManager
{
    /** @var SearchIndexer */
    protected $searchIndexer;

    /** @var ActivityManager */
    protected $activityManager;

    public function __construct(
        ObjectManager $om,
        ActivityManager $activityManager,
        SearchIndexer $searchIndexer
    ) {
        parent::__construct(null, $om);
        $this->searchIndexer   = $searchIndexer;
        $this->activityManager = $activityManager;
    }

    #[\Override]
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        return $this->searchIndexer->getSimpleSearchQuery(
            $criteria['search'],
            $this->getOffset($page, $limit),
            $limit,
            $this->getSearchAliases(isset($criteria['from']) ? $criteria['from'] : [])
        );
    }

    /**
     * Get search aliases for specified entity class(es). By default returns all search aliases
     * for all entities which can be associated with an activity this manager id work with.
     *
     * @param string[] $entities
     *
     * @return string[]
     */
    protected function getSearchAliases(array $entities)
    {
        if (empty($entities)) {
            $entities = array_flip($this->activityManager->getActivityTargets($this->class));
        }
        $aliases = [];
        foreach ($entities as $targetEntityClass) {
            $alias = $this->searchIndexer->getEntityAlias($targetEntityClass);
            if (null !== $alias) {
                $aliases[] = $alias;
            }
        }

        return $aliases;
    }
}
