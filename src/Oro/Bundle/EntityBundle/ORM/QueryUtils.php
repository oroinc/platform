<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\DoctrineUtils\ORM\QueryUtils as BaseQueryUtils;
use Oro\Bundle\EntityBundle\Exception;

/**
 * @deprecated since 1.9. Use {@see Oro\Component\DoctrineUtils\ORM\QueryUtils} instead.
 */
class QueryUtils extends BaseQueryUtils
{
    /**
     * Gets the root entity alias of the query.
     * This method rethrows QueryException as InvalidEntityException to avoid BC break
     *
     * @param QueryBuilder $qb             The query builder
     * @param bool         $throwException Whether to throw exception in case the query does not have a root alias
     *
     * @return string|null
     *
     * @throws Exception\InvalidEntityException
     */
    public static function getSingleRootAlias(QueryBuilder $qb, $throwException = true)
    {
        try {
            return BaseQueryUtils::getSingleRootAlias($qb, $throwException);
        } catch (QueryException $e) {
            throw new Exception\InvalidEntityException($e->getMessage());
        }
    }
}
