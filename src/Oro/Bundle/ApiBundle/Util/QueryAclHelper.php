<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\EntitySerializer\EntityConfig;

/**
 * A helper class that can be used to get ORM query protected by ACL rules and API query modifiers.
 * @see \Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper
 * @see \Oro\Bundle\ApiBundle\Util\QueryModifierInterface
 */
class QueryAclHelper
{
    private AclProtectedQueryFactory $queryFactory;

    public function __construct(AclProtectedQueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * Gets ORM query protected by ACL rules and API query modifiers.
     *
     * IMPORTANT: be careful using this method because API query modifiers change the given query builder.
     *
     * @param QueryBuilder $qb
     * @param EntityConfig $config
     * @param RequestType  $requestType
     *
     * @return Query
     */
    public function protectQuery(QueryBuilder $qb, EntityConfig $config, RequestType $requestType)
    {
        $initialRequestType = $this->queryFactory->getRequestType();
        $this->queryFactory->setRequestType($requestType);
        try {
            return $this->queryFactory->getQuery($qb, $config);
        } finally {
            $this->queryFactory->setRequestType($initialRequestType);
        }
    }
}
