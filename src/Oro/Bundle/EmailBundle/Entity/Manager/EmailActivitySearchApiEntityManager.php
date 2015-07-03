<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivitySearchApiEntityManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

class EmailActivitySearchApiEntityManager extends ActivitySearchApiEntityManager
{
    /**
     * @param string          $class
     * @param ObjectManager   $om
     * @param ActivityManager $activityManager
     * @param SearchIndexer   $searchIndexer
     */
    public function __construct(
        $class,
        ObjectManager $om,
        ActivityManager $activityManager,
        SearchIndexer $searchIndexer
    ) {
        parent::__construct($om, $activityManager, $searchIndexer);
        $this->setClass($class);
    }

    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $searchQuery = parent::getListQueryBuilder($limit, $page, $criteria, $orderBy, $joins);

        if (!empty($criteria['email'])) {
            $searchQuery->andWhere(
                'email',
                SearchQuery::OPERATOR_CONTAINS,
                $criteria['email']
            );
        }

        return $searchQuery;
    }
}
