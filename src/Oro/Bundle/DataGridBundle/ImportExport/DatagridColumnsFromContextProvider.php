<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager as DatagridManager;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

/**
 * Provides datagrid columns for given import-export context by gridName and gridParameters
 * - applies formatting;
 * - sorts columns according to their "order";
 * - excludes non-renderable columns.
 */
class DatagridColumnsFromContextProvider implements DatagridColumnsFromContextProviderInterface
{
    private DatagridManager $datagridManager;

    private DatagridStateProviderInterface $columnsStateProvider;

    public function __construct(DatagridManager $datagridManager, DatagridStateProviderInterface $columnsStateProvider)
    {
        $this->datagridManager = $datagridManager;
        $this->columnsStateProvider = $columnsStateProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnsFromContext(ContextInterface $context): array
    {
        if (!$context->hasOption('gridName')) {
            throw new InvalidConfigurationException(
                'Configuration of datagrid export processor must contain "gridName" option.'
            );
        }

        $datagrid = $this->getDatagrid($context);
        $datagridConfiguration = $datagrid->getConfig();
        $columns = $datagridConfiguration->offsetGet(Configuration::COLUMNS_KEY);
        $columnsState = $this->columnsStateProvider->getState($datagridConfiguration, $datagrid->getParameters());

        return $this->applyState($columns, $columnsState);
    }

    /**
     * Updates columns with columns state:
     * - updates properties of columns with actual values taken from state - "order" and "renderable";
     * - sorts columns by "order" property;
     * - excludes non-renderable columns;
     */
    private function applyState(array $columns, array $state): array
    {
        // Gets columns orders from columns state.
        $orders = array_column($state, ColumnsStateProvider::ORDER_FIELD_NAME);

        // Sorts columns according to orders.
        array_multisort($orders, $columns);

        // Excludes non-renderable columns.
        return array_filter(
            $columns,
            static fn (string $name) => $state[$name][ColumnsStateProvider::RENDER_FIELD_NAME],
            ARRAY_FILTER_USE_KEY
        );
    }

    private function getDatagrid(ContextInterface $context): DatagridInterface
    {
        return $this->datagridManager->getDatagrid(
            $context->getOption('gridName'),
            $context->getOption('gridParameters', [])
        );
    }
}
