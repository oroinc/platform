<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\ImportExport;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportIdFetcher;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Component\DependencyInjection\ServiceLink;

class DatagridExportIdFetcherTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateWithRequiredArgs()
    {
        new DatagridExportIdFetcher($this->createGridManagerLinkMock(), $this->createEventDispatcherMock());
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Configuration of datagrid export reader must contain "gridName".
     */
    public function testThrowInvalidConfigurationExceptionIfSettingContextWithoutGridName()
    {
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(false)
        ;

        $fetcher = new DatagridExportIdFetcher($this->createGridManagerLinkMock(), $this->createEventDispatcherMock());
        $fetcher->setImportExportContext($context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldAddRequiredParametersToQuery()
    {
        $gridConfig = $this->createDatagridConfigurationMock();
        $gridConfig
            ->expects($this->once())
            ->method('offsetGet')
            ->with('columns')
            ->willReturn('SomeColumns')
        ;

        $classMetadata = $this->createClassMetadataMock();
        $classMetadata
            ->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('IdentifierName')
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with('RootEntity')
            ->willReturn($classMetadata)
        ;

        $query = $this->createQueryMock();
        $query
            ->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([6 => 6, 3 => 3, 8 => 8])
        ;

        $qb = $this->createQueryBuilderMock();
        $qb
            ->expects($this->at(0))
            ->method('getRootAliases')
            ->willReturn(['RootAlias'])
        ;
        $qb
            ->expects($this->at(1))
            ->method('getEntityManager')
            ->willReturn($em)
        ;
        $qb
            ->expects($this->at(2))
            ->method('getRootEntities')
            ->willReturn(['RootEntity'])
        ;
        $qb
            ->expects($this->at(3))
            ->method('indexBy')
            ->with('RootAlias', 'RootAlias.IdentifierName')
            ->willReturn($qb)
        ;
        $qb
            ->expects($this->at(4))
            ->method('select')
            ->with('RootAlias.IdentifierName', 'RootAlias.IdentifierName')
            ->willReturn($qb)
        ;
        $qb
            ->expects($this->at(5))
            ->method('setFirstResult')
            ->with(null)
            ->willReturn($qb)
        ;
        $qb
            ->expects($this->at(6))
            ->method('setMaxResults')
            ->with(null)
            ->willReturn($qb)
        ;
        $qb
            ->expects($this->at(7))
            ->method('getQuery')
            ->willReturn($query)
        ;

        $dataSource = $this->createDatasourceMock();
        $dataSource
            ->expects($this->at(0))
            ->method('getQueryBuilder')
            ->willReturn($qb)
        ;

        $grid = $this->createDatagridMock();
        $grid
            ->expects($this->at(0))
            ->method('getConfig')
            ->willReturn($gridConfig)
        ;

        $grid
            ->expects($this->at(1))
            ->method('getAcceptedDatasource')
            ->willReturn($dataSource)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->at(0))
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(true)
        ;
        $context
            ->expects($this->at(1))
            ->method('getOption')
            ->with('gridName')
            ->willReturn('someGridName')
        ;
        $context
            ->expects($this->at(2))
            ->method('getOption')
            ->with('gridParameters')
            ->willReturn('someGridParameters')
        ;
        $context
            ->expects($this->at(3))
            ->method('setValue')
            ->with('columns', 'SomeColumns')
        ;

        $manager = $this->createManagerMock();
        $manager
            ->expects($this->once())
            ->method('getDatagrid')
            ->with('someGridName', 'someGridParameters')
            ->willReturn($grid)
        ;

        $gridManagerLink = $this->createGridManagerLinkMock();
        $gridManagerLink
            ->expects($this->once())
            ->method('getService')
            ->willReturn($manager)
        ;

        $eventDispatcher = $this->createEventDispatcherMock();
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(OrmResultBeforeQuery::NAME)
        ;

        $fetcher = new DatagridExportIdFetcher($gridManagerLink, $eventDispatcher);
        $fetcher->setImportExportContext($context);

        $result = $fetcher->getGridDataIds();

        $this->assertEquals([6, 3, 8], $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | AbstractQuery
     */
    private function createQueryMock()
    {
        return $this->createMock(AbstractQuery::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | ClassMetadata
     */
    private function createClassMetadataMock()
    {
        return $this->createMock(ClassMetadata::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | QueryBuilder
     */
    private function createQueryBuilderMock()
    {
        return $this->createMock(QueryBuilder::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | EntityManager
     */
    private function createEntityManagerMock()
    {
        return $this->createMock(EntityManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | OrmDatasource
     */
    private function createDatasourceMock()
    {
        return $this->createMock(OrmDatasource::class);
    }

    private function createManagerMock()
    {
        return $this->createMock(Manager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | DatagridConfiguration
     */
    private function createDatagridConfigurationMock()
    {
        return $this->createMock(DatagridConfiguration::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | DatagridInterface
     */
    private function createDatagridMock()
    {
        return $this->createMock(DatagridInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | ContextInterface
     */
    private function createContextMock()
    {
        return $this->createMock(ContextInterface::class);
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | ServiceLink
     */
    private function createGridManagerLinkMock()
    {
        return $this->createMock(ServiceLink::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | EventDispatcherInterface
     */
    private function createEventDispatcherMock()
    {
        return $this->createMock(EventDispatcherInterface::class);
    }
}
