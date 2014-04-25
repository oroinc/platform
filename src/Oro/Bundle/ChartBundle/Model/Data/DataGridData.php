<?php

namespace Oro\Bundle\ChartBundle\Model\Data;

use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface as DataGridManager;

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
     * {@inheritdoc}
     */
    public function toArray()
    {
        if (null === $this->data) {
            $resultData = $this->dataGridManager
                ->getDatagrid(
                    $this->dataGridName,
                    array(
                        PagerInterface::PAGER_ROOT_PARAM => array(PagerInterface::DISABLED_PARAM => true)
                    )
                )
                ->getData();
            $this->data = $resultData['data'];
        }

        return $this->data;
    }
}
