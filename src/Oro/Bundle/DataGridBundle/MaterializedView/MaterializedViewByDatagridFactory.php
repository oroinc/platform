<?php

namespace Oro\Bundle\DataGridBundle\MaterializedView;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\PlatformBundle\Entity\MaterializedView;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewManager;

/**
 * Factory that creates {@see MaterializedView} from the datagrid ORM datasource query.
 */
class MaterializedViewByDatagridFactory
{
    private MaterializedViewManager $materializedViewManager;

    public function __construct(MaterializedViewManager $materializedViewManager)
    {
        $this->materializedViewManager = $materializedViewManager;
    }

    public function createByDatagrid(DatagridInterface $datagrid): MaterializedView
    {
        $datasource = $datagrid->getAcceptedDatasource();
        if (!$datasource instanceof OrmDatasource) {
            throw new \LogicException(
                sprintf(
                    'Datasource was expected to be an instance of %s, got %s for the datagrid %s',
                    OrmDatasource::class,
                    get_class($datasource),
                    $datagrid->getName()
                )
            );
        }

        return $this->materializedViewManager->createByQuery($datasource->getResultsQuery());
    }
}
