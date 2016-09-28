<?php

namespace Oro\Bundle\SearchBundle\Search\Repository;

use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

abstract class AbstractSearchQueryRepository
{
    /**
     * @var QueryFactoryInterface
     */
    protected $queryFactory;

    /**
     * @param QueryFactoryInterface $queryFactory
     */
    public function __construct(QueryFactoryInterface $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * @return SearchQueryInterface
     */
    protected function getQueryBuilder()
    {
        return $this->queryFactory->create();
    }
}
