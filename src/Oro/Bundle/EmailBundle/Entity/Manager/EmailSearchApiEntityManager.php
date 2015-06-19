<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\Query;

use Oro\Bundle\SearchBundle\Query\Query as SearchQueryBuilder;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivitySearchApiEntityManager;

class EmailSearchApiEntityManager extends ActivitySearchApiEntityManager
{
    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        // Create from
        $from = $this->getSearchAliases(isset($criteria['from']) ? $criteria['from'] : []);

        /** @var SearchQueryBuilder $query  - Get query builder with select instance */
        $queryBuilder = $this->searchIndexer->select();
        $queryBuilder->from($from);

        if (isset($criteria['email'])) {
            $queryBuilder->andWhere('email', SearchQueryBuilder::OPERATOR_NOT_CONTAINS, $criteria['email']);
        }

        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($limit * ($page -1));

        return $queryBuilder;
    }
}
