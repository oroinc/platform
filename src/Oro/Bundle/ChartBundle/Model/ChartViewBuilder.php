<?php

namespace Oro\Bundle\ChartBundle\Model;

use Oro\Bundle\DataGridBundle\Datagrid\Manager as DataGridManager;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataGridData;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;

class ChartViewBuilder
{
    /**
     * @var DataGridManager
     */
    protected $dataGridManager;

    /**
     * @var DataInterface
     */
    protected $data;

    /**
     * @var DataInterface
     */
    protected $transformer;

    /**
     * @param DataGridManager $manager
     */
    public function __construct(DataGridManager $manager)
    {
        $this->dataGridManager = $manager;
    }

    /**
     * Set chart data
     *
     * @param DataInterface $data
     * @return ChartViewBuilder
     */
    public function setData(DataInterface $data)
    {
        $this->data = $data;
    }

    /**
     * Set chart data as array
     *
     * @param array $data
     * @return ChartViewBuilder
     */
    public function setArrayData(array $data)
    {
        $this->data = $this->setData(new ArrayData($data));
    }

    /**
     * Set chart data as grid name, grid data will used
     *
     * @param array $name
     * @return ChartViewBuilder
     */
    public function setDataGridName($name)
    {
        $this->data = $this->setData(new DataGridData($this->dataGridManager, $name));
    }

    /**
     * Set mapping
     *
     * @param array $mapping
     * @return ChartViewBuilder
     */
    public function setDataTransformMapping(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * Set chart config, it should include
     *
     * @param array $config
     * @return ChartViewBuilder
     */
    public function setChartConfig(array $config)
    {

    }

    /**
     * Build chart view
     *
     * @return ChartViewInterface
     */
    public function getView()
    {
        if ($this->mapping) {
            $data = new DataTransformerData($this->mapping, $this->data);
        }
    }
}
