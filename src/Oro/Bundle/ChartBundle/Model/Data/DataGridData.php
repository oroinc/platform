<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

/**
 * Return chart data based on the results of the data grid source.
 */
class DataGridData implements DataInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var DatagridInterface
     */
    protected $datagrid;

    public function __construct(DatagridInterface $datagrid)
    {
        $this->datagrid = $datagrid;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        if ($this->data) {
            return $this->data;
        }

        $this->data = array_map(
            fn (ResultRecord $record) => $record->getDataArray(),
            $this->datagrid->getAcceptedDatasource()->getResults()
        );

        return $this->data;
    }
}
