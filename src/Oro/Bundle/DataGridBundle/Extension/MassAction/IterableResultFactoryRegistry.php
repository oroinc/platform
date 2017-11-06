<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;

/**
 * Registry for IterableResultFactory services.
 */
class IterableResultFactoryRegistry
{
    /**
     * @var IterableResultFactory[]
     */
    private $factories = [];

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
    ): IterableResultInterface {
        foreach ($this->factories as $factory) {
            if ($factory->isApplicable($dataSource)) {
                return $factory
                    ->createIterableResult($dataSource, $actionConfiguration, $gridConfiguration, $selectedItems);
            }
        }

        throw new LogicException(
            sprintf('No IterableResultFactory found for "%s" datasource type', get_class($dataSource))
        );
    }

    /**
     * @param IterableResultFactoryInterface $factory
     */
    public function addFactory(IterableResultFactoryInterface $factory)
    {
        $this->factories[] = $factory;
    }
}
