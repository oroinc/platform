<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

/**
 * Provides datagrid columns for given import-export context from either:
 * 1) datagrid columns stored in context;
 * 2) columns from datagrid configuration;
 *
 * - applies formatting;
 * - sorts columns according to their "order";
 * - excludes non-renderable columns.
 */
class DatagridColumnsFromContextProvider implements DatagridColumnsFromContextProviderInterface
{
    /** @var Manager */
    private $datagridManager;

    /** @var DatagridStateProviderInterface */
    private $columnsStateProvider;

    /**
     * @param Manager $datagridManager
     * @param DatagridStateProviderInterface $columnsStateProvider
     */
    public function __construct(Manager $datagridManager, DatagridStateProviderInterface $columnsStateProvider)
    {
        $this->datagridManager = $datagridManager;
        $this->columnsStateProvider = $columnsStateProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnsFromContext(ContextInterface $context): array
    {
        // Get columns from import-export context.
        $columns = $context->getValue('columns');

        // Try to get columns from datagrid configuration.
        if (!$columns && $context->hasOption('gridName')) {
            $gridConfig = $this->getDatagridConfiguration($context);
            $columns = $gridConfig->offsetGet(Configuration::COLUMNS_KEY);
        }

        if (!$columns) {
            throw new InvalidConfigurationException(
                'Configuration of datagrid export processor must contain "gridName" or "columns" options.'
            );
        }

        return $this->applyState($columns, $context);
    }

    /**
     * Updates columns with columns state:
     * - updates properties of columns with actual values taken from state - "order" and "renderable";
     * - sorts columns by "order" property;
     * - excludes non-renderable columns;
     *
     * @param array $columns
     * @param ContextInterface $context
     *
     * @return array
     */
    private function applyState(array $columns, ContextInterface $context): array
    {
        if (!$context->hasOption('gridName') || !$context->hasOption('gridParameters')) {
            return $columns;
        }

        // We need datagrid configuration to get state from columnsStateProvider.
        $datagridConfiguration = $this->getDatagridConfiguration($context);
        // We override columns configuration because datagrid manager returns initial configuration which is not
        // altered by listeners or extensions, while we assume that "$columnsConfig" is the latest columns
        // configuration.
        $datagridConfiguration->offsetSet(Configuration::COLUMNS_KEY, $columns);

        $columnsState = $this->columnsStateProvider
            ->getState($datagridConfiguration, $context->getOption('gridParameters'));

        // Gets columns orders.
        $orders = array_column($columnsState, ColumnsStateProvider::ORDER_FIELD_NAME);

        // Sorts columns according to orders.
        array_multisort($orders, $columns);

        // Updates properties of columns with actual values taken from state.
        $columns = array_replace_recursive($columns, $columnsState);

        // Excludes non-renderable columns.
        return array_filter($columns, function (array $column) {
            return $column[ColumnsStateProvider::RENDER_FIELD_NAME];
        });
    }

    /**
     * @param ContextInterface $context
     *
     * @return DatagridConfiguration
     */
    private function getDatagridConfiguration(ContextInterface $context): DatagridConfiguration
    {
        $gridName = $context->getOption('gridName');

        return $this->datagridManager->getConfigurationForGrid($gridName);
    }
}
