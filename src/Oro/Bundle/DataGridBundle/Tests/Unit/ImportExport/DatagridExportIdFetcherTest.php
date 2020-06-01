<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\ImportExport;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryExecutorInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportIdFetcher;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DatagridExportIdFetcherTest extends OrmTestCase
{
    protected $em;

    /** @var QueryExecutorInterface */
    protected $queryExecutor;

    protected function setUp(): void
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Test' => 'Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Entity'
            ]
        );
    }

    public function testCreateWithRequiredArgs()
    {
        new DatagridExportIdFetcher(
            $this->createGridManagerLinkMock(),
            $this->createEventDispatcherMock(),
            $this->createQueryExecutorMock()
        );
    }

    public function testThrowInvalidConfigurationExceptionIfSettingContextWithoutGridName()
    {
        $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration of datagrid export reader must contain "gridName".');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(false);

        $fetcher = new DatagridExportIdFetcher(
            $this->createGridManagerLinkMock(),
            $this->createEventDispatcherMock(),
            $this->createQueryExecutorMock()
        );
        $fetcher->setImportExportContext($context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldAddRequiredParametersToQuery()
    {
        $gridConfig = $this->createDataGridConfigurationMock();

        $classMetadata = $this->createClassMetadataMock();

        $em = $this->createEntityManagerMock($classMetadata);

        $query = new Query($this->em);

        $qb = $this->createQueryBuilderMock();
        $qb
            ->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['RootAlias']);

        $qb
            ->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $qb
            ->expects($this->once())
            ->method('getRootEntities')
            ->willReturn(['RootEntity']);

        $qb
            ->expects($this->once())
            ->method('indexBy')
            ->with('RootAlias', 'RootAlias.IdentifierName')
            ->willReturn($qb);

        $qb
            ->expects($this->once())
            ->method('select')
            ->with('RootAlias.IdentifierName')
            ->willReturn($qb);

        $qb
            ->expects($this->once())
            ->method('setFirstResult')
            ->with(null)
            ->willReturn($qb);

        $qb
            ->expects($this->once())
            ->method('setMaxResults')
            ->with(null)
            ->willReturn($qb);

        $qb
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $dataSource = $this->createDatasourceMock($qb);

        $grid = $this->createDatagridMock($gridConfig, $dataSource);

        $context = $this->createContextMock();
        $context
            ->expects($this->at(0))
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(true);

        $context
            ->expects($this->at(1))
            ->method('getOption')
            ->with('gridName')
            ->willReturn('someGridName');

        $context
            ->expects($this->at(2))
            ->method('getOption')
            ->with('gridParameters')
            ->willReturn('someGridParameters');

        $context
            ->expects($this->at(3))
            ->method('setValue')
            ->with('columns', 'SomeColumns');

        $manager = $this->createManagerMock();
        $manager
            ->expects($this->once())
            ->method('getDatagrid')
            ->with('someGridName', 'someGridParameters')
            ->willReturn($grid);

        $gridManagerLink = $this->createGridManagerLinkMock();
        $gridManagerLink
            ->expects($this->once())
            ->method('getService')
            ->willReturn($manager);

        $eventDispatcher = $this->createEventDispatcherMock();
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(OrmResultBeforeQuery::NAME);

        $executeFunc = function (Query $query) {
            return $query->getArrayResult();
        };

        $queryExecutor = $this->createQueryExecutorMock();
        $queryExecutor->expects($this->once())
            ->method('execute')
            ->with(self::identicalTo($grid), self::identicalTo($query), $executeFunc)
            ->willReturn([6, 3, 8]);

        $fetcher = new DatagridExportIdFetcher($gridManagerLink, $eventDispatcher, $queryExecutor);
        $fetcher->setImportExportContext($context);

        $result = $fetcher->getGridDataIds();

        $this->assertEquals([6, 3, 8], $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetGridRootEntity()
    {
        $gridConfig = $this->createDataGridConfigurationMock();
        $qb = $this->createQueryBuilderMock();
        $qb
            ->expects($this->any())
            ->method('getRootEntities')
            ->willReturn(['RootEntity']);
        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource
            ->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);
        $grid = $this->createDatagridMock($gridConfig, $dataSource);
        $grid
            ->expects($this->once())
            ->method('getAcceptedDatasource')
            ->willReturn($dataSource);

        $context = $this->createContextMock();
        $context
            ->expects($this->at(0))
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(true);

        $manager = $this->createManagerMock();
        $manager
            ->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($grid);

        $gridManagerLink = $this->createGridManagerLinkMock();
        $gridManagerLink
            ->expects($this->once())
            ->method('getService')
            ->willReturn($manager);

        $eventDispatcher = $this->createEventDispatcherMock();
        $fetcher = new DatagridExportIdFetcher($gridManagerLink, $eventDispatcher, $this->createQueryExecutorMock());
        $fetcher->setImportExportContext($context);

        $this->assertEquals('RootEntity', $fetcher->getGridRootEntity());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldNotAddRequiredParametersToQuery()
    {
        $gridConfig = $this->createDatagridConfigurationMock();

        $classMetadata = $this->createClassMetadataMock();

        $em = $this->createEntityManagerMock($classMetadata);

        $qb = $this->createQueryBuilderMock();
        $qb
            ->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['RootAlias']);

        $qb
            ->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $qb
            ->expects($this->once())
            ->method('getRootEntities')
            ->willReturn(['RootEntity']);

        $qb
            ->expects($this->once())
            ->method('getDQLPart')
            ->willReturn(true);

        $dataSource = $this->createDatasourceMock($qb);

        $grid = $this->createDatagridMock($gridConfig, $dataSource);

        $context = $this->createContextMock();
        $context
            ->expects($this->at(0))
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(true);

        $context
            ->expects($this->at(1))
            ->method('getOption')
            ->with('gridName')
            ->willReturn('someGridName');

        $context
            ->expects($this->at(2))
            ->method('getOption')
            ->with('gridParameters')
            ->willReturn('someGridParameters');

        $context
            ->expects($this->at(3))
            ->method('setValue')
            ->with('columns', 'SomeColumns');

        $manager = $this->createManagerMock();
        $manager
            ->expects($this->once())
            ->method('getDatagrid')
            ->with('someGridName', 'someGridParameters')
            ->willReturn($grid);

        $gridManagerLink = $this->createGridManagerLinkMock();
        $gridManagerLink
            ->expects($this->once())
            ->method('getService')
            ->willReturn($manager);

        $eventDispatcher = $this->createEventDispatcherMock();
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(OrmResultBeforeQuery::NAME);

        $fetcher = new DatagridExportIdFetcher($gridManagerLink, $eventDispatcher, $this->createQueryExecutorMock());
        $fetcher->setImportExportContext($context);

        $result = $fetcher->getGridDataIds();

        $this->assertEquals([], $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testQueryWithoutTransformWithHaving()
    {
        $qb = $this->em
            ->getRepository('Test:Test')
            ->createQueryBuilder('e')
            ->having('COUNT(*) > 0');

        $gridConfig = $this->createDatagridConfigurationMock();

        $dataSource = $this->createDatasourceMock($qb);

        $grid = $this->createDatagridMock($gridConfig, $dataSource);

        $context = $this->createContextMock();
        $context
            ->expects($this->at(0))
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(true);

        $context
            ->expects($this->at(1))
            ->method('getOption')
            ->with('gridName')
            ->willReturn('someGridName');

        $context
            ->expects($this->at(2))
            ->method('getOption')
            ->with('gridParameters')
            ->willReturn('someGridParameters');

        $context
            ->expects($this->at(3))
            ->method('setValue')
            ->with('columns', 'SomeColumns');

        $manager = $this->createManagerMock();
        $manager
            ->expects($this->once())
            ->method('getDatagrid')
            ->with('someGridName', 'someGridParameters')
            ->willReturn($grid);

        $gridManagerLink = $this->createGridManagerLinkMock();
        $gridManagerLink
            ->expects($this->once())
            ->method('getService')
            ->willReturn($manager);

        $eventDispatcher = $this->createEventDispatcherMock();
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(OrmResultBeforeQuery::NAME);

        $fetcher = new DatagridExportIdFetcher($gridManagerLink, $eventDispatcher, $this->createQueryExecutorMock());
        $fetcher->setImportExportContext($context);

        $result = $fetcher->getGridDataIds();

        $this->assertEquals([], $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testQueryWithoutTransformWithGroupBy()
    {
        $qb = $this->em
            ->getRepository('Test:Test')
            ->createQueryBuilder('e')
            ->select('e.id')
            ->groupBy('e.id');

        $gridConfig = $this->createDatagridConfigurationMock();

        $dataSource = $this->createDatasourceMock($qb);

        $grid = $this->createDatagridMock($gridConfig, $dataSource);

        $context = $this->createContextMock();
        $context
            ->expects($this->at(0))
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(true);

        $context
            ->expects($this->at(1))
            ->method('getOption')
            ->with('gridName')
            ->willReturn('someGridName');

        $context
            ->expects($this->at(2))
            ->method('getOption')
            ->with('gridParameters')
            ->willReturn('someGridParameters');

        $context
            ->expects($this->at(3))
            ->method('setValue')
            ->with('columns', 'SomeColumns');

        $manager = $this->createManagerMock();
        $manager
            ->expects($this->once())
            ->method('getDatagrid')
            ->with('someGridName', 'someGridParameters')
            ->willReturn($grid);

        $gridManagerLink = $this->createGridManagerLinkMock();
        $gridManagerLink
            ->expects($this->once())
            ->method('getService')
            ->willReturn($manager);

        $eventDispatcher = $this->createEventDispatcherMock();
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(OrmResultBeforeQuery::NAME);

        $fetcher = new DatagridExportIdFetcher($gridManagerLink, $eventDispatcher, $this->createQueryExecutorMock());
        $fetcher->setImportExportContext($context);

        $result = $fetcher->getGridDataIds();

        $this->assertEquals([], $result);
    }

    public function testQueryWithTransform()
    {
        $qb = $this->em
            ->getRepository('Test:Test')
            ->createQueryBuilder('e')
            ->select('e.id')
            ->indexBy('e', 'e.id');

        $gridConfig = $this->createDatagridConfigurationMock();

        $dataSource = $this->createDatasourceMock($qb);

        $grid = $this->createDatagridMock($gridConfig, $dataSource);

        $context = $this->createContextMock();
        $context
            ->expects($this->at(0))
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(true);

        $context
            ->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['gridName', null, 'someGridName'],
                ['gridParameters', null, 'someGridParameters'],
            ]);

        $context
            ->expects($this->at(3))
            ->method('setValue')
            ->with('columns', 'SomeColumns');

        $manager = $this->createManagerMock();
        $manager
            ->expects($this->once())
            ->method('getDatagrid')
            ->with('someGridName', 'someGridParameters')
            ->willReturn($grid);

        $gridManagerLink = $this->createGridManagerLinkMock();
        $gridManagerLink
            ->expects($this->once())
            ->method('getService')
            ->willReturn($manager);

        $eventDispatcher = $this->createEventDispatcherMock();
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(OrmResultBeforeQuery::NAME);

        $executeFunc = function (Query $query) {
            return $query->getArrayResult();
        };

        $queryExecutor = $this->createQueryExecutorMock();
        $queryExecutor->expects($this->once())
            ->method('execute')
            ->with(self::identicalTo($grid), $qb->getQuery(), $executeFunc)
            ->willReturn([1]);

        $fetcher = new DatagridExportIdFetcher($gridManagerLink, $eventDispatcher, $queryExecutor);
        $fetcher->setImportExportContext($context);

        $result = $fetcher->getGridDataIds();

        $this->assertEquals([1], $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | ClassMetadata
     */
    private function createClassMetadataMock()
    {
        $classMetadata = $this->createMock(ClassMetadata::class);

        $classMetadata
            ->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('IdentifierName');

        return $classMetadata;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | QueryBuilder
     */
    private function createQueryBuilderMock()
    {
        return $this->createMock(QueryBuilder::class);
    }

    /**
     * @param  \PHPUnit\Framework\MockObject\MockObject | ClassMetadata $classMetadata
     * @return \PHPUnit\Framework\MockObject\MockObject | EntityManager
     */
    private function createEntityManagerMock($classMetadata)
    {
        $entityManager = $this->createMock(EntityManager::class);

        $entityManager
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with('RootEntity')
            ->willReturn($classMetadata);

        return $entityManager;
    }

    /**
     * @param  \PHPUnit\Framework\MockObject\MockObject | QueryBuilder $queryBuilder
     * @return \PHPUnit\Framework\MockObject\MockObject | OrmDatasource
     */
    private function createDatasourceMock($queryBuilder)
    {
        $dataSource = $this->createMock(OrmDatasource::class);

        $dataSource
            ->expects($this->at(0))
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        return $dataSource;
    }

    private function createManagerMock()
    {
        return $this->createMock(Manager::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | DatagridConfiguration
     */
    private function createDataGridConfigurationMock()
    {
        $gridConfig = $this->createMock(DatagridConfiguration::class);

        $gridConfig
            ->expects($this->once())
            ->method('offsetGet')
            ->with('columns')
            ->willReturn('SomeColumns');

        return $gridConfig;
    }

    /**
     * @param  \PHPUnit\Framework\MockObject\MockObject | DatagridConfiguration $gridConfig
     * @param  \PHPUnit\Framework\MockObject\MockObject | OrmDatasource $dataSource
     * @return \PHPUnit\Framework\MockObject\MockObject | DatagridInterface
     */
    private function createDatagridMock($gridConfig, $dataSource)
    {
        $grid = $this->createMock(DatagridInterface::class);

        $grid
            ->expects($this->at(0))
            ->method('getConfig')
            ->willReturn($gridConfig);

        $grid
            ->expects($this->at(1))
            ->method('getAcceptedDatasource')
            ->willReturn($dataSource);

        return $grid;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | ContextInterface
     */
    private function createContextMock()
    {
        return $this->createMock(ContextInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | ServiceLink
     */
    private function createGridManagerLinkMock()
    {
        return $this->createMock(ServiceLink::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | EventDispatcherInterface
     */
    private function createEventDispatcherMock()
    {
        return $this->createMock(EventDispatcherInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject| QueryExecutorInterface
     */
    private function createQueryExecutorMock()
    {
        return $this->createMock(QueryExecutorInterface::class);
    }
}
