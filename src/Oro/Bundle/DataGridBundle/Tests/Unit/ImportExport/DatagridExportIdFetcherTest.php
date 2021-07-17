<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\ImportExport;

use Doctrine\Common\Annotations\AnnotationReader;
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

class DatagridExportIdFetcherTest extends OrmTestCase
{
    /**
     * @var \Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock
     */
    private $em;

    /**
     * @var ServiceLink|\PHPUnit\Framework\MockObject\MockObject
     */
    private $gridManagerLink;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var QueryExecutorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $queryExecutor;

    /**
     * @var DatagridExportIdFetcher
     */
    private $fetcher;

    protected function setUp(): void
    {
        $this->gridManagerLink = $this->createMock(ServiceLink::class);
        $this->context = $this->createMock(ContextInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->queryExecutor = $this->createMock(QueryExecutorInterface::class);

        $reader = new AnnotationReader();
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

        $this->fetcher = new DatagridExportIdFetcher(
            $this->gridManagerLink,
            $this->eventDispatcher,
            $this->queryExecutor
        );
    }

    public function testThrowInvalidConfigurationExceptionIfSettingContextWithoutGridName()
    {
        $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration of datagrid export reader must contain "gridName".');

        $this->context->expects($this->once())
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(false);

        $this->fetcher->setImportExportContext($this->context);
    }

    public function testGetGridRootEntity()
    {
        $qb = new QueryBuilder($this->em);
        $qb->from('Test:Test', 't')
            ->select('t.id');

        $this->assertGridCall($qb);
        $this->fetcher->setImportExportContext($this->context);

        $this->assertEquals('Test:Test', $this->fetcher->getGridRootEntity());
    }

    /**
     * @dataProvider getGridDataIdsDataProvider
     */
    public function testGetGridDataIds(callable $qbCallback, string $expectedDQL, string $resultKey)
    {
        $qb = $qbCallback($this->em);
        $grid = $this->assertGridCall($qb);
        $this->fetcher->setImportExportContext($this->context);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                new OrmResultBeforeQuery($grid, $qb),
                OrmResultBeforeQuery::NAME
            );

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            $this->isType('string'),
            [[$resultKey => 1], [$resultKey => 2], [$resultKey => 1]]
        );
        $this->queryExecutor->expects($this->once())
            ->method('execute')
            ->willReturnCallback(
                function (DatagridInterface $datagrid, Query $query, $executeFunc) use ($expectedDQL) {
                    // Check for expected DQL
                    self::assertEquals($expectedDQL, $query->getDQL());
                    // Check that Optimized DQL can be converted to SQL
                    self::assertNotEmpty($query->getSQL());

                    return $executeFunc($query);
                }
            );

        $this->assertEquals([1, 2], $this->fetcher->getGridDataIds());
    }

    /**
     * @return array[]
     */
    public function getGridDataIdsDataProvider(): array
    {
        return [
            'simple select' => [
                function ($em) {
                    return (new QueryBuilder($em))
                        ->from('Test:Test', 't')
                        ->select('t.id', 't.name');
                },
                'SELECT DISTINCT t.id FROM Test:Test t INDEX BY t.id',
                'id_0'
            ],
            'simple select with order by' => [
                function ($em) {
                    return (new QueryBuilder($em))
                        ->from('Test:Test', 't')
                        ->select('t.id', 't.name')
                        ->orderBy('t.name', 'ASC');
                },
                'SELECT DISTINCT t.id FROM Test:Test t INDEX BY t.id',
                'id_0'
            ],
            'select with group by' => [
                function ($em) {
                    return (new QueryBuilder($em))
                        ->from('Test:Test', 't')
                        ->select('t.id', 't.name')
                        ->groupBy('t.id', 't.name');
                },
                'SELECT DISTINCT t.id FROM Test:Test t INDEX BY t.id',
                'id_0'
            ],
            'select with group by and having' => [
                function ($em) {
                    return (new QueryBuilder($em))
                        ->from('Test:Test', 't')
                        ->select('COUNT(t.id) as idCount', 't.name')
                        ->groupBy('t.id', 't.name')
                        ->having('idCount > 0');
                },
                'SELECT COUNT(t.id) as idCount, t.name, t.id FROM Test:Test t INDEX BY t.id ' .
                'GROUP BY t.id, t.name, t.id HAVING idCount > 0',
                'id_2'
            ],
        ];
    }

    /**
     * @param QueryBuilder $qb
     * @return DatagridInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertGridCall(QueryBuilder $qb)
    {
        $gridConfig = $this->createMock(DatagridConfiguration::class);
        $gridConfig->expects($this->any())
            ->method('offsetGet')
            ->with('columns')
            ->willReturn('SomeColumns');

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects($this->any())
            ->method('getConfig')
            ->willReturn($gridConfig);
        $grid->expects($this->any())
            ->method('getAcceptedDatasource')
            ->willReturn($dataSource);
        $grid->expects($this->any())
            ->method('getAcceptedDatasource')
            ->willReturn($dataSource);

        $this->context->expects($this->any())
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(true);

        $manager = $this->createMock(Manager::class);
        $manager->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($grid);

        $this->gridManagerLink->expects($this->once())
            ->method('getService')
            ->willReturn($manager);

        return $grid;
    }
}
