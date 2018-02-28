<?php

namespace Oro\Bundle\DataGridBundle\Extension\Sorter;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

class OrmSorterExtension extends AbstractSorterExtension
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            $config->isOrmDatasource()
            && parent::isApplicable($config);
    }

    /**
     * {@inheritdoc}
     */
    protected function addSorterToDatasource(array $sorter, $direction, DatasourceInterface $datasource)
    {
        /* @var OrmDatasource $datasource */
        $datasource->getQueryBuilder()->addOrderBy($sorter['data_name'], $direction);
    }

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        parent::visitDatasource($config, $datasource);

        // ensure that ORDER BY is specified explicitly, in case if sorting was not requested
        // use sorting by
        // - the primary key of the root table if the query does not have GROUP BY
        // - the first column of GROUP BY if this clause exists
        // if ORDER BY is not given, the order of rows is not predictable and they are returned
        // in whatever order SQL server finds fastest to produce.
        /** @var OrmDatasource $datasource */
        $qb = $datasource->getQueryBuilder();
        $orderBy = $qb->getDQLPart('orderBy');
        if (empty($orderBy)) {
            $groupBy = $qb->getDQLPart('groupBy');
            if (empty($groupBy)) {
                $rootEntities = $qb->getRootEntities();
                $rootEntity = reset($rootEntities);
                if ($rootEntity) {
                    $rootAliases = $qb->getRootAliases();
                    $rootAlias = reset($rootAliases);
                    $rootIdFieldNames = $qb->getEntityManager()
                        ->getClassMetadata($rootEntity)
                        ->getIdentifierFieldNames();
                    $qb->addOrderBy($rootAlias . '.' . reset($rootIdFieldNames));
                }
            } else {
                /** @var Expr\GroupBy $firstExpr */
                $firstExpr = reset($groupBy);
                $exprParts = $firstExpr->getParts();
                $qb->addOrderBy(reset($exprParts));
            }
        }
    }
}
