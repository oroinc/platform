<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\ImportExport;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryExecutorInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportIdFetcher;
use Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Entity\Test as Entity;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DatagridExportIdFetcherTest extends OrmTestCase
{
    private EntityManagerMock $em;

    private ServiceLink|\PHPUnit\Framework\MockObject\MockObject $gridManagerLink;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context;

    private QueryExecutorInterface|\PHPUnit\Framework\MockObject\MockObject $queryExecutor;

    private DatagridExportIdFetcher $fetcher;

    protected function setUp(): void
    {
        $this->gridManagerLink = $this->createMock(ServiceLink::class);
        $this->context = $this->createMock(ContextInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->queryExecutor = $this->createMock(QueryExecutorInterface::class);

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $this->fetcher = new DatagridExportIdFetcher(
            $this->gridManagerLink,
            $this->eventDispatcher,
            $this->queryExecutor
        );
    }

    public function testThrowInvalidConfigurationExceptionIfSettingContextWithoutGridName(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration of datagrid export reader must contain "gridName".');

        $this->context->expects(self::once())
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(false);

        $this->fetcher->setImportExportContext($this->context);
    }

    public function testGetGridRootEntity(): void
    {
        $qb = new QueryBuilder($this->em);
        $qb->from(Entity::class, 't')
            ->select('t.id');

        $this->assertGridCall($qb);
        $this->fetcher->setImportExportContext($this->context);

        self::assertEquals(Entity::class, $this->fetcher->getGridRootEntity());
    }

    /**
     * @dataProvider getGridDataIdsDataProvider
     */
    public function testGetGridDataIds(callable $qbCallback, string $expectedDQL, string $resultKey): void
    {
        $qb = $qbCallback($this->em);
        $grid = $this->assertGridCall($qb);
        $this->fetcher->setImportExportContext($this->context);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new OrmResultBeforeQuery($grid, $qb),
                OrmResultBeforeQuery::NAME
            );

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            self::isType('string'),
            [[$resultKey => 1], [$resultKey => 2], [$resultKey => 1]]
        );
        $this->queryExecutor->expects(self::once())
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

        self::assertEquals([1, 2], $this->fetcher->getGridDataIds());
    }

    public function getGridDataIdsDataProvider(): array
    {
        return [
            'simple select' => [
                function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Entity::class, 't')
                        ->select('t.id', 't.name');
                },
                'SELECT DISTINCT t.id FROM ' . Entity::class . ' t INDEX BY t.id',
                'id_0',
            ],
            'simple select with order by' => [
                function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Entity::class, 't')
                        ->select('t.id', 't.name')
                        ->orderBy('t.name', 'ASC');
                },
                'SELECT DISTINCT t.id FROM ' . Entity::class . ' t INDEX BY t.id',
                'id_0',
            ],
            'select with group by' => [
                function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Entity::class, 't')
                        ->select('t.id', 't.name')
                        ->groupBy('t.id', 't.name');
                },
                'SELECT DISTINCT t.id FROM ' . Entity::class . ' t INDEX BY t.id',
                'id_0',
            ],
            'select with group by and having' => [
                function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Entity::class, 't')
                        ->select('COUNT(t.id) as idCount', 't.name')
                        ->groupBy('t.id', 't.name')
                        ->having('idCount > 0');
                },
                'SELECT COUNT(t.id) as idCount, t.name, t.id FROM ' . Entity::class . ' t INDEX BY t.id ' .
                'GROUP BY t.id, t.name, t.id HAVING idCount > 0',
                'id_2',
            ],
        ];
    }

    private function assertGridCall(QueryBuilder $qb): DatagridInterface
    {
        $gridConfig = $this->createMock(DatagridConfiguration::class);
        $gridConfig->expects(self::any())
            ->method('offsetGet')
            ->with('columns')
            ->willReturn('SomeColumns');

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects(self::any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects(self::any())
            ->method('getConfig')
            ->willReturn($gridConfig);
        $grid->expects(self::any())
            ->method('getAcceptedDatasource')
            ->willReturn($dataSource);
        $grid->expects(self::any())
            ->method('getAcceptedDatasource')
            ->willReturn($dataSource);
        $results = ResultsObject::create(['options' => ['totalRecords' => 4242]]);
        $grid->expects(self::any())
            ->method('getData')
            ->willReturn($results);

        $this->context->expects(self::any())
            ->method('hasOption')
            ->with('gridName')
            ->willReturn(true);

        $manager = $this->createMock(Manager::class);
        $manager->expects(self::once())
            ->method('getDatagrid')
            ->willReturn($grid);

        $this->gridManagerLink->expects(self::once())
            ->method('getService')
            ->willReturn($manager);

        return $grid;
    }

    public function testGetTotalRecords(): void
    {
        $qb = new QueryBuilder($this->em);
        $this->assertGridCall($qb);
        $this->fetcher->setImportExportContext($this->context);

        self::assertEquals(4242, $this->fetcher->getTotalRecords());
    }
}
