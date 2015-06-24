<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class ActivitySearchApiEntityManager extends ApiEntityManager
{
    /** @var SearchIndexer */
    protected $searchIndexer;

    /** @var ActivityManager */
    protected $activityManager;

    /**
     * @param ObjectManager   $om
     * @param ActivityManager $activityManager
     * @param SearchIndexer   $searchIndexer
     */
    public function __construct(
        ObjectManager $om,
        ActivityManager $activityManager,
        SearchIndexer $searchIndexer
    ) {
        parent::__construct(null, $om);
        $this->searchIndexer   = $searchIndexer;
        $this->activityManager = $activityManager;
    }

    /**
     * {@inheritdoc}
     */
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
     * Returns the list of fields responsible to store activity associations for the given activity entity type
     *
     * @return array
     */
    public function getAssociations()
    {
        return $this->activityManager->getActivityTargets($this->class);
    }

    /**
     * Get search aliases for specified entity class(es). By default returns all associated entities.
     *
     * @param string[] $from
     *
     * @return array
     */
    protected function getSearchAliases(array $from)
    {
        $entities = empty($from)
            ? $this->activityManager->getActivityTargets($this->class)
            : array_flip($from);
        $aliases  = array_intersect_key($this->searchIndexer->getEntitiesListAliases(), $entities);

        return array_values($aliases);
    }
}
