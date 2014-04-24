<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

use Oro\Bundle\DataGridBundle\Datagrid\Manager as DataGridManager;

class DataGridData implements DataInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var DataGridManager
     */
    protected $dataGridManager;

    /**
     * @var string
     */
    protected $dataGridName;

    /**
     * @param DataGridManager $manager
     * @param string $dataGridName
     */
    public function __construct(DataGridManager $manager, $dataGridName)
    {
        $this->dataGridManager = $manager;
        $this->dataGridName = $dataGridName;
    }

    /**
     * Reads data from data grid
     *
     * @return mixed
     */
    public function toArray()
    {
        if (null === $this->data) {
            $dataGrid = $this->dataGridManager->getDatagrid($this->dataGridName);
            $this->data = $dataGrid->getData();
        }

        return $this->data;
    }
}
