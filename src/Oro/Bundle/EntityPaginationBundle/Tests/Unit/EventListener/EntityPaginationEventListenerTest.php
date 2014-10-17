<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityPaginationBundle\EventListener\EntityPaginationListener;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

class EntityPaginationListenerTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_NAME = 'test_entity';
    const GRID_NAME   = 'test_grid';

    /** @var EntityPaginationListener */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    public function setUp()
    {
        $this->storage = $this
            ->getMock('Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage');

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new EntityPaginationListener($this->storage, $this->doctrineHelper);
    }

    /**
     *
     */
    public function testOnBuildAfter()
    {
        $fieldName       = 'id';
        $currentIds      =  [45, 78, 25, 8, 32, 40, 64, 84, 67, 4];
        $totalRecords    = 41;
        $state           = [
            '_pager'   => [
                '_page'     => 2,
                '_per_page' => 10
            ],
            '_sort_by' => [],
            '_filter'  => []
        ];
        $paginationState = [
            'current_ids' => $currentIds,
            'state'       => $state,
            'total'       => $totalRecords,
            'previous_id' => null,
            'next_id'     => null
        ];
        $config          = [
            'options' => [
                'entity_pagination' => true,
            ],
        ];

        $parameters = new ParameterBag($state);

        $dataSource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $acceptor = $this->getMock('Oro\Bundle\DataGridBundle\Extension\Acceptor');

        $dataGrid->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue(DatagridConfiguration::create($config)));

        $dataGrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($dataSource));

        $dataGrid->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));

        $dataGrid->expects($this->once())
            ->method('getName')
            ->will($this->returnValue(self::GRID_NAME));

        $dataGrid->expects($this->once())
            ->method('getAcceptor')
            ->will($this->returnValue($acceptor));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder->expects($this->once())
            ->method('getRootEntities')
            ->will($this->returnValue([self::ENTITY_NAME]));

        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $acceptor->expects($this->once())
            ->method('acceptResult')
            ->with($this->isInstanceOf('Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject'))
            ->will($this->returnCallback(
                function (ResultsObject $result) use ($totalRecords) {
                    $result->offsetSetByPath(EntityPaginationListener::TOTAL_RECORDS_PATH, $totalRecords);
                }
            ));

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::ENTITY_NAME)
            ->will($this->returnValue($fieldName));

        $this->storage->expects($this->once())
            ->method('addData')
            ->with(self::ENTITY_NAME, self::GRID_NAME, $paginationState);

        $resultRecords = [];
        foreach ($currentIds as $id) {
            $resultRecords[] = new ResultRecord(['id' => $id]);
        }

        $event = new OrmResultAfter($dataGrid, $resultRecords);
        $this->listener->onResultAfter($event);
    }
}
