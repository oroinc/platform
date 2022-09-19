<?php

namespace Oro\Component\DoctrineUtils\MaterializedView;

use Doctrine\ORM\Query;
use Oro\Component\DoctrineUtils\DBAL\Schema\MaterializedView;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;

/**
 * Factory that creates {@see MaterializedView} model from the ORM query.
 */
class MaterializedViewByQueryFactory
{
    public function createByQuery(Query $query, string $name, bool $withData = true): MaterializedView
    {
        $clonedQuery = QueryUtil::cloneQuery($query);
        $clonedQuery->setFirstResult(null)->setMaxResults(null);
        $sqlDefinition = QueryUtil::getExecutableSql($clonedQuery);

        return new MaterializedView($name, $sqlDefinition, $withData);
    }
}
