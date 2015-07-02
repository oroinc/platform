<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\Query;

use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivitySearchApiEntityManager;

class EmailActivitySearchApiEntityManager extends ActivitySearchApiEntityManager
{
    /**
     * The target field that we use for searching activities
     */
    const SEARCH_EMAIL_TARGET_FIELD = 'email';

    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $searchQuery = parent::getListQueryBuilder($limit, $page, $criteria, $orderBy, $joins);

        if (isset($criteria['email'])) {
            $searchQuery->andWhere(
                self::SEARCH_EMAIL_TARGET_FIELD,
                SearchQuery::OPERATOR_CONTAINS,
                $criteria['email']
            );
        }
        return $searchQuery;
    }
}
