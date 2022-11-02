<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Storage;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;
use Oro\Bundle\EntityPaginationBundle\Storage\StorageDataCollector;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\HttpFoundation\Request;

class StorageDataCollectorTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_NAME = 'test_entity';
    private const GRID_NAME   = 'test_grid';

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityPaginationStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var EntityPaginationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $paginationManager;

    /** @var StorageDataCollector */
    private $collector;

    protected function setUp(): void
    {
        $this->datagridManager = $this->createMock(Manager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->storage = $this->createMock(EntityPaginationStorage::class);
        $this->paginationManager = $this->createMock(EntityPaginationManager::class);

        $datagridManagerLink = $this->createMock(ServiceLink::class);
        $datagridManagerLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->datagridManager);

        $this->collector = new StorageDataCollector(
            $datagridManagerLink,
            $this->doctrineHelper,
            $this->storage,
            $this->paginationManager
        );
    }

    public function testCollectWithDisabledPagination()
    {
        $this->paginationManager->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->datagridManager->expects($this->never())
            ->method('getDatagridByRequestParams');

        $this->assertFalse($this->collector->collect($this->getGridRequest(), 'test'));
    }

    public function testCollectWithEmptyGridRequest()
    {
        $this->paginationManager->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->datagridManager->expects($this->never())
            ->method('getDatagridByRequestParams');

        $this->assertFalse($this->collector->collect(new Request(['grid' => '']), 'test'));
    }

    public function testCollectWithInvalidGridName()
    {
        $invalidGridName = 'invalid';

        $this->paginationManager->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
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
        $this->paginationManager->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->buildDataGrid();
        $this->storage->expects($this->never())
            ->method('hasData');

        $this->assertFalse($this->collector->collect($this->getGridRequest(), 'test'));
    }

    public function testCollectDataAlreadySet()
    {
        $scope = EntityPaginationManager::VIEW_SCOPE;

        $this->paginationManager->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->buildDataGrid(true);
        $this->storage->expects($this->once())
            ->method('hasData')
            ->with(self::ENTITY_NAME, $this->isType('string'), $scope)
            ->willReturn(true);
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

        $this->paginationManager->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->paginationManager->expects($this->atLeastOnce())
            ->method('getLimit')
            ->willReturn($paginationLimit);
        $this->buildDataGrid(true, $state, $entityIds, $paginationLimit);
        $this->storage->expects($this->once())
            ->method('hasData')
            ->with(self::ENTITY_NAME, $hash, $scope)
            ->willReturn(false);
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

        $this->paginationManager->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->paginationManager->expects($this->once())
            ->method('getLimit')
            ->willReturn($paginationLimit);
        $this->buildDataGrid(true, $state, $entityIds, $paginationLimit);
        $this->storage->expects($this->once())
            ->method('hasData')
            ->with(self::ENTITY_NAME, $hash, $scope)
            ->willReturn(false);
        $this->storage->expects($this->once())
            ->method('setData')
            ->with(self::ENTITY_NAME, $hash, [], $scope);

        $this->assertTrue($this->collector->collect($this->getGridRequest(), $scope));
    }

    private function buildDataGrid(
        bool $isApplicable = false,
        array $state = [],
        array $entityIds = [],
        int $entitiesLimit = 0
    ): void {
        $metadata = ['state' => $state];
        $metadataObject = MetadataObject::create($metadata);
        $identifierField = 'id';

        $this->paginationManager->expects($this->any())
            ->method('isDatagridApplicable')
            ->willReturn($isApplicable);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->any())
            ->method('getRootEntities')
            ->willReturn([self::ENTITY_NAME]);
        $queryBuilder->expects($this->any())
            ->method('setFirstResult')
            ->with(0);
        $queryBuilder->expects($this->any())
            ->method('setMaxResults')
            ->with($entitiesLimit);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->with(self::ENTITY_NAME)
            ->willReturn(new ClassMetadata(self::ENTITY_NAME));
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::ENTITY_NAME)
            ->willReturn($identifierField);

        $entities = [];
        foreach ($entityIds as $id) {
            $entities[] = new ResultRecord([$identifierField => $id]);
        }

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);
        $dataSource->expects($this->any())
            ->method('getResults')
            ->willReturn($entities);

        $acceptor = $this->createMock(Acceptor::class);
        $result = ResultsObject::create(['data' => []]);
        $acceptor->expects($this->any())
            ->method('acceptResult')
            ->with($result)
            ->willReturnCallback(function ($result) use ($entities) {
                return $result->setTotalRecords(count($entities));
            });

        $dataGrid = $this->createMock(DatagridInterface::class);
        $dataGrid->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadataObject);
        $config = ['options' => ['entity_pagination' => true]];
        $configObject = DatagridConfiguration::create($config);
        $dataGrid->expects($this->any())
            ->method('getConfig')
            ->willReturn($configObject);
        $dataGrid->expects($this->any())
            ->method('getAcceptor')
            ->willReturn($acceptor);
        $dataGrid->expects($this->any())
            ->method('getDatasource')
            ->willReturn($dataSource);
        $dataGrid->expects($this->any())
            ->method('getParameters')
            ->willReturn(new ParameterBag($state));

        $this->datagridManager->expects($this->once())
            ->method('getDatagridByRequestParams')
            ->with(self::GRID_NAME)
            ->willReturn($dataGrid);
    }

    private function getGridRequest(): Request
    {
        return new Request(['grid' => [self::GRID_NAME => []]]);
    }
}
