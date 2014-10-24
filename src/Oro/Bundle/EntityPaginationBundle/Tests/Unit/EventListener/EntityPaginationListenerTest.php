<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;
use Oro\Bundle\EntityPaginationBundle\EventListener\EntityPaginationListener;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;

class EntityPaginationListenerTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_NAME = 'test_entity';
    const GRID_NAME   = 'test_grid';

    /** @var EntityPaginationListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $storage;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $datagridManager;

    public function setUp()
    {
        $this->datagridManager = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = $this->getMockBuilder('Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new EntityPaginationListener($this->datagridManager, $this->doctrineHelper, $this->storage);
    }

    public function testOnResultAfterSystemPaginationDisabled()
    {
        $this->storage->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(false));

        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $dataGrid->expects($this->never())
            ->method('getConfig');

        $this->listener->onResultAfter(new OrmResultAfter($dataGrid));
    }

    public function testOnResultAfterGridPaginationDisabled()
    {
        $this->storage->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->storage->expects($this->never())
            ->method('hasData');

        $dataGrid = $this->createGridMock(false);

        $this->listener->onResultAfter(new OrmResultAfter($dataGrid));
    }

    /**
     * @param int $limit
     * @param int $total
     * @dataProvider noSetDataDataProvider
     */
    public function testOnResultAfterNoSetData($limit, $total)
    {
        $this->storage->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->storage->expects($this->any())
            ->method('getLimit')
            ->will($this->returnValue($limit));
        if ($total <= $limit) {
            $this->storage->expects($this->once())
                ->method('hasData')
                ->with(self::ENTITY_NAME, $this->isType('string'))
                ->will($this->returnValue(true));
        } else {
            $this->storage->expects($this->never())
                ->method('hasData');
        }

        // total count > entities limit
        $dataGrid = $this->createGridMock(true, [], $total);

        $this->listener->onResultAfter(new OrmResultAfter($dataGrid));
    }

    /**
     * @return array
     */
    public function noSetDataDataProvider()
    {
        return array(
            'count > limit' => [
                'limit' => 100,
                'count' => 50,
            ],
            'count < limit' => [
                'limit' => 100,
                'count' => 200,
            ],
        );
    }

    /**
     * @param bool $isPaginationEnabled
     * @param array $gridState
     * @param int $totalCount
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createGridMock($isPaginationEnabled, array $gridState = [], $totalCount = 100)
    {
        $config = ['options' => ['entity_pagination' => $isPaginationEnabled]];
        $configObject = DatagridConfiguration::create($config);

        $metadata = ['state' => $gridState];
        $metadataObject = MetadataObject::create($metadata);

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->any())
            ->method('getRootEntities')
            ->will($this->returnValue([self::ENTITY_NAME]));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->with(self::ENTITY_NAME)
            ->will($this->returnValue(new ClassMetadata(self::ENTITY_NAME)));

        $dataSource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $dataSource->expects($this->any())
            ->method('getQueryBuilder')
            ->will($this->returnValue($queryBuilder));
        $acceptor = $this->getMock('Oro\Bundle\DataGridBundle\Extension\Acceptor');
        $acceptor->expects($this->any())
            ->method('acceptResult')
            ->with($this->isInstanceOf('Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject'))
            ->will($this->returnCallback(
                function (ResultsObject $result) use ($totalCount) {
                    $result->offsetSetByPath(PagerInterface::TOTAL_PATH_PARAM, $totalCount);
                }
            ));

        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $dataGrid->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(self::GRID_NAME));
        $dataGrid->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($configObject));
        $dataGrid->expects($this->any())
            ->method('getMetadata')
            ->will($this->returnValue($metadataObject));
        $dataGrid->expects($this->any())
            ->method('getDatasource')
            ->will($this->returnValue($dataSource));
        $dataGrid->expects($this->any())
            ->method('getAcceptor')
            ->will($this->returnValue($acceptor));

        return $dataGrid;
    }

    public function testOnResultAfterSetData()
    {
        $limit = 100;
        $total = 50;

        $state = ['filters' => ['a' => 'b'], 'sorters' => ['c' => 'd']];
        $stateHash = md5(json_encode(array_merge($state, ['total' => $total])));
        $parameters = [
            OrmFilterExtension::FILTER_ROOT_PARAM  => $state['filters'],
            OrmSorterExtension::SORTERS_ROOT_PARAM => $state['sorters'],
            PagerInterface::PAGER_ROOT_PARAM       => [
                PagerInterface::PAGE_PARAM     => 1,
                PagerInterface::PER_PAGE_PARAM => $limit
            ],
        ];

        $fieldName = 'id';
        $entityIds = [1, 2, 3, 4, 5];
        $entityResult = [];
        foreach ($entityIds as $id) {
            $entityResult[] = [$fieldName => $id];
        }

        $this->storage->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->storage->expects($this->any())
            ->method('getLimit')
            ->will($this->returnValue($limit));
        $this->storage->expects($this->once())
            ->method('hasData')
            ->with(self::ENTITY_NAME, $this->isType('string'))
            ->will($this->returnValue(false));
        $this->storage->expects($this->at(3))
            ->method('setData')
            ->with(self::ENTITY_NAME, $stateHash, []);
        $this->storage->expects($this->at(5))
            ->method('setData')
            ->with(self::ENTITY_NAME, $stateHash, $entityIds);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::ENTITY_NAME)
            ->will($this->returnValue($fieldName));

        $fullDataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $fullDataGrid->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(ResultsObject::create(['data' => $entityResult])));

        $this->datagridManager->expects($this->once())
            ->method('getDataGrid')
            ->with(self::GRID_NAME, $parameters)
            ->will($this->returnValue($fullDataGrid));

        $dataGrid = $this->createGridMock(true, $state, $total);

        $this->listener->onResultAfter(new OrmResultAfter($dataGrid));
    }
}
