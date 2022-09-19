<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datasource\Orm;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\Configs\YamlProcessor;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\ParameterBinder;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryExecutorInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Stub\SomeClass;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolver;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrmDatasourceTest extends \PHPUnit\Framework\TestCase
{
    private YamlProcessor|\PHPUnit\Framework\MockObject\MockObject $processor;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private ParameterBinder|\PHPUnit\Framework\MockObject\MockObject $parameterBinder;

    private QueryHintResolver|\PHPUnit\Framework\MockObject\MockObject $queryHintResolver;

    private QueryExecutorInterface|\PHPUnit\Framework\MockObject\MockObject $queryExecutor;

    private OrmDatasource $datasource;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(YamlProcessor::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->parameterBinder = $this->createMock(ParameterBinder::class);
        $this->queryHintResolver = $this->createMock(QueryHintResolver::class);
        $this->queryExecutor = $this->createMock(QueryExecutorInterface::class);

        $this->datasource = new OrmDatasource(
            $this->processor,
            $this->eventDispatcher,
            $this->parameterBinder,
            $this->queryHintResolver,
            $this->queryExecutor
        );
    }

    private function getConfig(): array
    {
        return [
            'query' => [
                'select' => ['t'],
                'from' => [
                    ['table' => 'Test', 'alias' => 't'],
                ],
            ],
        ];
    }

    private function getQuery(): Query
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $configuration = new Configuration();
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        return new Query($em);
    }

    public function testProcess(): void
    {
        $config = $this->getConfig();
        $datagrid = $this->createMock(DatagridInterface::class);
        $qb = $this->createMock(QueryBuilder::class);
        $countQb = $this->createMock(QueryBuilder::class);

        $this->processor->expects(self::once())
            ->method('processQuery')
            ->with($config)
            ->willReturn($qb);
        $this->processor->expects(self::once())
            ->method('processCountQuery')
            ->with($config)
            ->willReturn($countQb);

        $datagrid->expects(self::once())
            ->method('setDatasource')
            ->with(
                self::logicalAnd(
                    self::equalTo($this->datasource),
                    self::logicalNot(self::identicalTo($this->datasource))
                )
            )
            ->willReturnSelf();

        $this->datasource->process($datagrid, $config);

        self::assertSame($datagrid, $this->datasource->getDatagrid());
        self::assertSame($qb, $this->datasource->getQueryBuilder());
        self::assertSame($countQb, $this->datasource->getCountQb());
        self::assertSame([], $this->datasource->getQueryHints());
        self::assertSame([], $this->datasource->getCountQueryHints());
    }

    public function testProcessWithHints(): void
    {
        $config = $this->getConfig();
        $config['hints'] = ['some_hint'];
        $config['count_hints'] = ['some_count_hint'];

        $datagrid = $this->createMock(DatagridInterface::class);
        $qb = $this->createMock(QueryBuilder::class);
        $countQb = $this->createMock(QueryBuilder::class);

        $this->processor->expects(self::once())
            ->method('processQuery')
            ->with($config)
            ->willReturn($qb);
        $this->processor->expects(self::once())
            ->method('processCountQuery')
            ->with($config)
            ->willReturn($countQb);

        $datagrid->expects(self::once())
            ->method('setDatasource')
            ->with(
                self::logicalAnd(
                    self::equalTo($this->datasource),
                    self::logicalNot(self::identicalTo($this->datasource))
                )
            )
            ->willReturnSelf();

        $this->datasource->process($datagrid, $config);

        self::assertSame($datagrid, $this->datasource->getDatagrid());
        self::assertSame($qb, $this->datasource->getQueryBuilder());
        self::assertSame($countQb, $this->datasource->getCountQb());
        self::assertSame($config['hints'], $this->datasource->getQueryHints());
        self::assertSame($config['count_hints'], $this->datasource->getCountQueryHints());
    }

    public function testGetResults(): void
    {
        $config = $this->getConfig();
        $config['hints'] = ['some_hint'];

        $datagrid = $this->createMock(DatagridInterface::class);
        $query = $this->getQuery();
        $qb = $this->createMock(QueryBuilder::class);
        $rows = [['key' => 'value']];
        $records = [new ResultRecord($rows[0])];
        $recordsAfterEvent = [new ResultRecord(['key' => 'updated_value'])];

        $this->processor->expects(self::once())
            ->method('processQuery')
            ->with($config)
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($query), $config['hints']);
        $this->queryExecutor->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($datagrid), self::identicalTo($query), self::isNull())
            ->willReturn($rows);

        $this->eventDispatcher->expects(self::exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(OrmResultBeforeQuery::class), OrmResultBeforeQuery::NAME],
                [self::isInstanceOf(OrmResultBefore::class), OrmResultBefore::NAME],
                [self::isInstanceOf(OrmResultAfter::class), OrmResultAfter::NAME]
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (OrmResultBeforeQuery $event) {
                    return $event;
                }),
                new ReturnCallback(function (OrmResultBefore $event) {
                    return $event;
                }),
                new ReturnCallback(function (OrmResultAfter $event) use ($records, $recordsAfterEvent) {
                    self::assertEquals($records, $event->getRecords());
                    $event->setRecords($recordsAfterEvent);

                    return $event;
                })
            );

        $this->datasource->process($datagrid, $config);
        $resultRecords = $this->datasource->getResults();

        self::assertSame($recordsAfterEvent, $resultRecords);
    }

    public function testBindParametersWorks(): void
    {
        $parameters = ['foo'];
        $append = true;

        $config = $this->getConfig();
        $datagrid = $this->createMock(DatagridInterface::class);
        $qb = $this->createMock(QueryBuilder::class);

        $this->processor->expects(self::once())
            ->method('processQuery')
            ->willReturn($qb);

        $this->parameterBinder->expects(self::once())
            ->method('bindParameters')
            ->with($datagrid, $parameters, $append);

        $this->datasource->process($datagrid, $config);
        $this->datasource->bindParameters($parameters, $append);
    }

    public function testBindParametersFailsWhenDatagridIsEmpty(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method is not allowed when datasource is not processed.');

        $this->datasource->bindParameters(['foo']);
    }

    public function testClone(): void
    {
        $config = $this->getConfig();
        $datagrid = $this->createMock(DatagridInterface::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $qb = new QueryBuilder($em);
        $qb->from(SomeClass::class, 't')->select('t');
        $countQb = new QueryBuilder($em);
        $qb->from(SomeClass::class, 't')->select('COUNT(t)');

        $this->processor->expects(self::once())
            ->method('processQuery')
            ->willReturn($qb);
        $this->processor->expects(self::once())
            ->method('processCountQuery')
            ->willReturn($countQb);

        $this->datasource->process($datagrid, $config);
        $this->datasource = clone $this->datasource;

        self::assertEquals($qb, $this->datasource->getQueryBuilder());
        self::assertNotSame($qb, $this->datasource->getQueryBuilder());

        self::assertEquals($countQb, $this->datasource->getCountQb());
        self::assertNotSame($countQb, $this->datasource->getCountQb());
    }

    public function testCloneWithoutCountQueryBuilder(): void
    {
        $config = $this->getConfig();
        $datagrid = $this->createMock(DatagridInterface::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $qb = new QueryBuilder($em);
        $qb->from(SomeClass::class, 't')->select('t');

        $this->processor->expects(self::once())
            ->method('processQuery')
            ->willReturn($qb);
        $this->processor->expects(self::once())
            ->method('processCountQuery')
            ->willReturn(null);

        $this->datasource->process($datagrid, $config);
        $this->datasource = clone $this->datasource;

        self::assertEquals($qb, $this->datasource->getQueryBuilder());
        self::assertNotSame($qb, $this->datasource->getQueryBuilder());

        self::assertNull($this->datasource->getCountQb());
    }

    public function testGetResultsQuery(): void
    {
        $config = $this->getConfig();
        $config['hints'] = ['some_hint'];

        $datagrid = $this->createMock(DatagridInterface::class);
        $query = $this->getQuery();
        $qb = $this->createMock(QueryBuilder::class);

        $this->processor->expects(self::once())
            ->method('processQuery')
            ->with($config)
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($query), $config['hints']);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(OrmResultBeforeQuery::class), OrmResultBeforeQuery::NAME],
                [self::isInstanceOf(OrmResultBefore::class), OrmResultBefore::NAME],
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(static fn (OrmResultBeforeQuery $event) => $event),
                new ReturnCallback(static fn (OrmResultBefore $event) => $event)
            );

        $this->datasource->process($datagrid, $config);

        self::assertSame($query, $this->datasource->getResultsQuery());
    }

    public function testGetRootEntityName(): void
    {
        $config = $this->getConfig();
        $config['hints'] = ['some_hint'];

        $datagrid = $this->createMock(DatagridInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->processor->expects(self::once())
            ->method('processQuery')
            ->with($config)
            ->willReturn($queryBuilder);

        $entityAliases = [\stdClass::class, 'AcmeEntity'];
        $queryBuilder
            ->expects(self::once())
            ->method('getRootEntities')
            ->willReturn($entityAliases);

        $this->datasource->process($datagrid, $config);

        self::assertSame($entityAliases[0], $this->datasource->getRootEntityName());
    }
}
