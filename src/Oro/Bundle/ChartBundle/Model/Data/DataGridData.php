<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

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

    /**
     * @param DatagridInterface $datagrid
     */
    public function __construct(DatagridInterface $datagrid)
    {
        $this->datagrid = $datagrid;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        if (null === $this->data) {
            $resultData = $this->datagrid->getData();
            $this->data = $resultData['data'];
        }

        return $this->data;
    }
}
