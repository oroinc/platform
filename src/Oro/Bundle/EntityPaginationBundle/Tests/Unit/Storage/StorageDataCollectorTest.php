<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Storage;

use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\EntityPaginationBundle\Storage\StorageDataCollector;
use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;

class StorageDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_NAME = 'test_entity';
    const GRID_NAME   = 'test_grid';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagridManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $paginationManager;

    /**
     * @var StorageDataCollector
     */
    protected $collector;

    protected function setUp()
    {
        $this->datagridManager = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = $this->getMockBuilder('Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paginationManager =
            $this->getMockBuilder('Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager')
                ->disableOriginalConstructor()
                ->getMock();

        $this->collector = new StorageDataCollector(
            $this->datagridManager,
            $this->doctrineHelper,
            $this->aclHelper,
            $this->storage,
            $this->paginationManager
        );
    }

    public function testCollectWithDisabledPagination()
    {
        $this->setPaginationEnabled(false);
        $this->datagridManager->expects($this->never())
            ->method('getDatagridByRequestParams');

        $this->assertFalse($this->collector->collect($this->getGridRequest(), 'test'));
    }

    public function testCollectWithEmptyGridRequest()
    {
        $this->setPaginationEnabled(true);
        $this->datagridManager->expects($this->never())
            ->method('getDatagridByRequestParams');

        $this->assertFalse($this->collector->collect(new Request(['grid' => '']), 'test'));
    }

    public function testCollectWithInvalidGridName()
    {
        $invalidGridName = 'invalid';

        $this->setPaginationEnabled(true);
        $this->datagridManager->expects($this->once())
            ->method('getDatagridByRequestParams')
            ->with($invalidGridName)
            ->willThrowException(new \RuntimeException());
        $this->storage->expects($this->never())
            ->method('hasData');

        $this->assertFalse($this->collector->collect(new Request(['grid' => [$invalidGridName => null]]), 'test'));
    }

    public function testCollectGridNotApplicable()
    {
        $this->setPaginationEnabled(true);
        $this->buildDataGrid();
        $this->storage->expects($this->never())
            ->method('hasData');

        $this->assertFalse($this->collector->collect($this->getGridRequest(), 'test'));
    }

    public function testCollectDataAlreadySet()
    {
        $scope = EntityPaginationManager::VIEW_SCOPE;

        $this->setPaginationEnabled(true);
        $this->buildDataGrid(true);
        $this->storage->expects($this->once())
            ->method('hasData')
            ->with(self::ENTITY_NAME, $this->isType('string'), $scope)
            ->will($this->returnValue(true));
        $this->storage->expects($this->never())
            ->method('setData');

        $this->assertTrue($this->collector->collect($this->getGridRequest(), $scope));
    }

    public function testCollectAllowedNumberOfEntities()
    {
        $state = ['filters' => [1 => 2], 'sorters' => [3 => 4]];
        $hash = md5(json_encode($state));
        $scope = EntityPaginationManager::VIEW_SCOPE;
        $entityIds = [1, 2, 3];
        $paginationLimit = 100;

        $this->setPaginationEnabled(true);
        $this->setPaginationLimit($paginationLimit);
        $this->buildDataGrid(true, $state, $scope, $entityIds, $paginationLimit);
        $this->storage->expects($this->once())
            ->method('hasData')
            ->with(self::ENTITY_NAME, $hash, $scope)
            ->will($this->returnValue(false));
        $this->storage->expects($this->once())
            ->method('setData')
            ->with(self::ENTITY_NAME, $hash, $entityIds, $scope);

        $this->assertTrue($this->collector->collect($this->getGridRequest(), $scope));
    }

    public function testCollectNotAllowedNumberOfEntities()
    {
        $state = ['filters' => [], 'sorters' => []];
        $hash = md5(json_encode($state));
        $scope = EntityPaginationManager::VIEW_SCOPE;
        $entityIds = [1, 2, 3];
        $paginationLimit = 2;

        $this->setPaginationEnabled(true);
        $this->setPaginationLimit($paginationLimit);
        $this->buildDataGrid(true, $state, $scope, $entityIds, $paginationLimit);
        $this->storage->expects($this->once())
            ->method('hasData')
            ->with(self::ENTITY_NAME, $hash, $scope)
            ->will($this->returnValue(false));
        $this->storage->expects($this->once())
            ->method('setData')
            ->with(self::ENTITY_NAME, $hash, [], $scope);

        $this->assertTrue($this->collector->collect($this->getGridRequest(), $scope));
    }

    /**
     * @param bool $isApplicable
     * @param array $state
     * @param string $scope
     * @param array $entityIds
     * @param int $entitiesLimit
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function buildDataGrid(
        $isApplicable = false,
        array $state = [],
        $scope = EntityPaginationManager::VIEW_SCOPE,
        array $entityIds = [],
        $entitiesLimit = 0
    ) {
        $metadata = ['state' => $state];
        $metadataObject = MetadataObject::create($metadata);
        $permission = EntityPaginationManager::getPermission($scope);
        $identifierField = 'id';

        $this->paginationManager->expects($this->any())
            ->method('isDatagridApplicable')
            ->will($this->returnValue($isApplicable));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->any())
            ->method('getRootEntities')
            ->will($this->returnValue([self::ENTITY_NAME]));
        $queryBuilder->expects($this->any())
            ->method('setFirstResult')
            ->with(0);
        $queryBuilder->expects($this->any())
            ->method('setMaxResults')
            ->with($entitiesLimit);

        $entities = [];
        foreach ($entityIds as $id) {
            $entities[] = [$identifierField => $id];
        }

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMockForAbstractClass();
        $query->expects($this->any())
            ->method('execute')
            ->will($this->returnValue($entities));

        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->with($queryBuilder, $permission)
            ->will($this->returnValue($query));
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->with(self::ENTITY_NAME)
            ->will($this->returnValue(new ClassMetadata(self::ENTITY_NAME)));
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::ENTITY_NAME)
            ->will($this->returnValue($identifierField));

        $dataSource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $dataSource->expects($this->any())
            ->method('getQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $acceptor = $this->getMock('Oro\Bundle\DataGridBundle\Extension\Acceptor');
        $result = ResultsObject::create(['data' => []]);
        $acceptor->expects($this->any())
            ->method('acceptResult')
            ->with($result)
            ->willReturnCallback(
                function ($result) use ($entities) {
                    return $result->setTotalRecords(count($entities));
                }
            );

        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $dataGrid->expects($this->any())
            ->method('getMetadata')
            ->will($this->returnValue($metadataObject));
        $config = ['options' => ['entity_pagination' => true]];
        $configObject = DatagridConfiguration::create($config);
        $dataGrid->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($configObject));
        $dataGrid->expects($this->any())
            ->method('getAcceptor')
            ->will($this->returnValue($acceptor));
        $dataGrid->expects($this->any())
            ->method('getDatasource')
            ->will($this->returnValue($dataSource));
        $dataGrid->expects($this->any())
            ->method('getParameters')
            ->will($this->returnValue(new ParameterBag($state)));

        $this->datagridManager->expects($this->once())
            ->method('getDatagridByRequestParams')
            ->with(self::GRID_NAME)
            ->will($this->returnValue($dataGrid));

        return $dataGrid;
    }

    /**
     * @return Request
     */
    protected function getGridRequest()
    {
        return new Request(['grid' => [self::GRID_NAME => []]]);
    }

    /**
     * @param bool $enabled
     */
    protected function setPaginationEnabled($enabled)
    {
        $this->paginationManager->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue($enabled));
    }

    /**
     * @param int $limit
     */
    protected function setPaginationLimit($limit)
    {
        $this->paginationManager->expects($this->any())
            ->method('getLimit')
            ->will($this->returnValue($limit));
    }
}
