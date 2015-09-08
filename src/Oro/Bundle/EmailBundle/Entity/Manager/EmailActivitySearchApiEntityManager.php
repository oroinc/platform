<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivitySearchApiEntityManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Query\Query as SearchQueryBuilder;

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
        $searchQueryBuilder = parent::getListQueryBuilder($limit, $page, $criteria, $orderBy, $joins);

        if (!empty($criteria['emails'])) {
            $this->prepareSearchEmailCriteria($searchQueryBuilder, $criteria['emails']);
        }

        return $searchQueryBuilder;
    }

    /**
     * @param SearchQueryBuilder $searchQueryBuilder
     * @param string[]           $emails
     */
    protected function prepareSearchEmailCriteria(SearchQueryBuilder $searchQueryBuilder, $emails = [])
    {
        $searchCriteria = $searchQueryBuilder->getCriteria();
        foreach ($emails as $email) {
            $searchCriteria->orWhere(
                $searchCriteria->expr()->contains('email', $email)
            );
        }
    }
}
