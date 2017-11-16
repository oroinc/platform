<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;

/**
 * Interface for factory to create ResultIteratorInterface object for provided Datasource.
 */
interface IterableResultFactoryInterface
{
    /**
     * @param DatasourceInterface $dataSource
     * @return bool
     */
    public function isApplicable(DatasourceInterface $dataSource): bool;

    /**
     * @param DatasourceInterface $dataSource
     * @param ActionConfiguration $actionConfiguration
     * @param DatagridConfiguration $gridConfiguration
     * @param SelectedItems $selectedItems
     * @return IterableResultInterface
     * @throws LogicException
     */
    public function createIterableResult(
        DatasourceInterface $dataSource,
        ActionConfiguration $actionConfiguration,
        DatagridConfiguration $gridConfiguration,
        SelectedItems $selectedItems
    ): IterableResultInterface;
}
