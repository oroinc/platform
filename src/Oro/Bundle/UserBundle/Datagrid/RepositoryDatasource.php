<?php


namespace Oro\Bundle\UserBundle\Datagrid;


use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class RepositoryDatasource implements DatasourceInterface
{
    /** @var  ResultRecordInterface[] */
    private $resultList;

    public function process(DatagridInterface $grid, array $config)
    {
        // TODO: Implement process() method.
        $grid->setDatasource(/* clone */$this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
        return $this->resultList;
    }
    
    public function setResults($resultList)
    {
        foreach ($resultList as $result) {
            $this->resultList[] = new ResultRecord($result);
        }
    }

}