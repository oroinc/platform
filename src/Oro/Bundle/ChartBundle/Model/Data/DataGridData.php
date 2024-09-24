<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

/**
 * Return chart data based on the results of the data grid source.
 */
class DataGridData implements DataInterface
{
    protected array $data = [];
    protected DatagridInterface $grid;

    public function __construct(DatagridInterface $grid)
    {
        $this->grid = $grid;
    }

    #[\Override]
    public function toArray(): array
    {
        if ($this->data) {
            return $this->data;
        }

        $this->data = array_map(
            fn (ResultRecord $record) => $record->getDataArray(),
            $this->grid->getAcceptedDatasource()->getResults()
        );

        return $this->data;
    }
}
