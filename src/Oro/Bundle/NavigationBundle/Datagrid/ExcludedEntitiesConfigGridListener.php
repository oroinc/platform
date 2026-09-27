<?php

namespace Oro\Bundle\NavigationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Hides the given entities from a config grid
 *
 * A workaround for BB-27972: normally such an entity is hidden with `mode: 'hidden'`, but that mode breaks
 * the schema of an entity that already exists. Remove this listener once BB-27972 is fixed.
 */
class ExcludedEntitiesConfigGridListener
{
    /**
     * @param string[] $excludedEntities
     */
    public function __construct(private readonly array $excludedEntities)
    {
    }

    public function onBuildAfter(BuildAfter $event): void
    {
        if (!$this->excludedEntities) {
            return;
        }

        $datasource = $event->getDatagrid()->getDatasource();
        if (!$datasource instanceof OrmDatasource) {
            return;
        }

        $queryBuilder = $datasource->getQueryBuilder();
        $parameterName = QueryBuilderUtil::generateParameterName('excludedEntities', $queryBuilder);
        $queryBuilder
            ->andWhere($queryBuilder->expr()->notIn(
                QueryBuilderUtil::sprintf('%s.className', QueryBuilderUtil::getSingleRootAlias($queryBuilder)),
                ':' . $parameterName
            ))
            ->setParameter($parameterName, $this->excludedEntities);
    }
}
