<?php

namespace Oro\Bundle\DataGridBundle\Extension\Board\Processor;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;

/**
 * Represents a datagrid processor for "board" mode.
 */
interface BoardProcessorInterface
{
    /**
     * Get options to use for board columns
     *
     * @param array $boardConfig
     * @param DatagridConfiguration $datagridConfig
     * @return array
     */
    public function getBoardOptions($boardConfig, DatagridConfiguration $datagridConfig);

    /**
     * Process grid datasource to return data for board
     *
     * @param DatasourceInterface $datasource
     * @param array $boardData
     * @param DatagridConfiguration $datagridConfig
     */
    public function processDatasource(
        DatasourceInterface $datasource,
        $boardData,
        DatagridConfiguration $datagridConfig
    );

    /**
     * Process grid datasource to return data for entity pagination
     *
     * @param DatasourceInterface $datasource
     * @param array $boardData
     * @param DatagridConfiguration $datagridConfig
     */
    public function processPaginationDatasource(
        DatasourceInterface $datasource,
        $boardData,
        DatagridConfiguration $datagridConfig
    );
}
