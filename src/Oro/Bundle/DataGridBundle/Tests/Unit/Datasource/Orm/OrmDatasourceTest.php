<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datasource\Orm;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\Configs\YamlProcessor;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryExecutorInterface;
use Oro\Bundle\DataGridBundle\Datasource\ParameterBinderInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Stub\SomeClass;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrmDatasourceTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrmDatasource */
    private $datasource;

    /** @var YamlProcessor|\PHPUnit\Framework\MockObject\MockObject */
    private $processor;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ParameterBinderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $parameterBinder;

    /** @var QueryHintResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $queryHintResolver;

    /** @var QueryExecutorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $queryExecutor;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(YamlProcessor::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->parameterBinder = $this->createMock(ParameterBinderInterface::class);
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

    /**
     * @return array
     */
    private function getConfig()
    {
        return [
            'query' => [
                'select' => ['t'],
                'from'   => [
                    ['table' => 'Test', 'alias' => 't']
                ]
            ]
        ];
    }

    /**
     * @return Query
     */
    private function getQuery()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $configuration = new Configuration();
        $em->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        return new Query($em);
    }

    public function testProcess()
    {
        $config = $this->getConfig();
        $datagrid = $this->createMock(DatagridInterface::class);
        $qb = $this->createMock(QueryBuilder::class);
        $countQb = $this->createMock(QueryBuilder::class);

        $this->processor->expects(static::once())
            ->method('processQuery')
            ->with($config)
            ->willReturn($qb);
        $this->processor->expects(static::once())
            ->method('processCountQuery')
            ->with($config)
            ->willReturn($countQb);

        $datagrid->expects(static::once())
            ->method('setDatasource')
            ->with(
                static::logicalAnd(
                    static::equalTo($this->datasource),
                    static::logicalNot(static::identicalTo($this->datasource))
                )
            )
            ->willReturnSelf();

        $this->datasource->process($datagrid, $config);

        static::assertSame($datagrid, $this->datasource->getDatagrid());
        static::assertSame($qb, $this->datasource->getQueryBuilder());
        static::assertSame($countQb, $this->datasource->getCountQb());
        static::assertSame([], $this->datasource->getQueryHints());
        static::assertSame([], $this->datasource->getCountQueryHints());
    }

    public function testProcessWithHints()
    {
        $config = $this->getConfig();
        $config['hints'] = ['some_hint'];
        $config['count_hints'] = ['some_count_hint'];

        $datagrid = $this->createMock(DatagridInterface::class);
        $qb = $this->createMock(QueryBuilder::class);
        $countQb = $this->createMock(QueryBuilder::class);

        $this->processor->expects($this->once())
            ->method('processQuery')
            ->with($config)
            ->willReturn($qb);
        $this->processor->expects($this->once())
            ->method('processCountQuery')
            ->with($config)
            ->willReturn($countQb);

        $datagrid->expects($this->once())
            ->method('setDatasource')
            ->with(
                static::logicalAnd(
                    static::equalTo($this->datasource),
                    static::logicalNot(static::identicalTo($this->datasource))
                )
            )
            ->willReturnSelf();

        $this->datasource->process($datagrid, $config);

        static::assertSame($datagrid, $this->datasource->getDatagrid());
        static::assertSame($qb, $this->datasource->getQueryBuilder());
        static::assertSame($countQb, $this->datasource->getCountQb());
        static::assertSame($config['hints'], $this->datasource->getQueryHints());
        static::assertSame($config['count_hints'], $this->datasource->getCountQueryHints());
    }

    public function testGetResults()
    {
        $config = $this->getConfig();
        $config['hints'] = ['some_hint'];

        $datagrid = $this->createMock(DatagridInterface::class);
        $query = $this->getQuery();
        $qb = $this->createMock(QueryBuilder::class);
        $rows = [['key' => 'value']];
        $records = [new ResultRecord($rows[0])];
        $recordsAfterEvent = [new ResultRecord(['key' => 'updated_value'])];

        $this->processor->expects($this->once())
            ->method('processQuery')
            ->with($config)
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $this->queryHintResolver->expects($this->once())
            ->method('resolveHints')
            ->with($this->identicalTo($query), $config['hints']);
        $this->queryExecutor->expects($this->once())
            ->method('execute')
            ->with($this->identicalTo($datagrid), $this->identicalTo($query), $this->isNull())
            ->willReturn($rows);

        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(new OrmResultBeforeQuery($datagrid, $qb), OrmResultBeforeQuery::NAME);
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(new OrmResultBefore($datagrid, $query), OrmResultBefore::NAME);
        $this->eventDispatcher->expects($this->at(2))
            ->method('dispatch')
            ->with(new OrmResultAfter($datagrid, $records, $query), OrmResultAfter::NAME)
            ->willReturnCallback(function (OrmResultAfter $event, $name) use ($recordsAfterEvent) {
                $event->setRecords($recordsAfterEvent);
            });

        $this->datasource->process($datagrid, $config);
        $resultRecords = $this->datasource->getResults();

        $this->assertSame($recordsAfterEvent, $resultRecords);
    }

    public function testBindParametersWorks()
    {
        $parameters = ['foo'];
        $append = true;

        $config = $this->getConfig();
        $datagrid = $this->createMock(DatagridInterface::class);
        $qb = $this->createMock(QueryBuilder::class);

        $this->processor->expects($this->once())
            ->method('processQuery')
            ->willReturn($qb);

        $this->parameterBinder->expects($this->once())
            ->method('bindParameters')
            ->with($datagrid, $parameters, $append);

        $this->datasource->process($datagrid, $config);
        $this->datasource->bindParameters($parameters, $append);
    }

    public function testBindParametersFailsWhenDatagridIsEmpty()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method is not allowed when datasource is not processed.');

        $this->datasource->bindParameters(['foo']);
    }

    public function testClone()
    {
        $config = $this->getConfig();
        $datagrid = $this->createMock(DatagridInterface::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $qb = new QueryBuilder($em);
        $qb->from(SomeClass::class, 't')->select('t');
        $countQb = new QueryBuilder($em);
        $qb->from(SomeClass::class, 't')->select('COUNT(t)');

        $this->processor->expects($this->once())
            ->method('processQuery')
            ->willReturn($qb);
        $this->processor->expects($this->once())
            ->method('processCountQuery')
            ->willReturn($countQb);

        $this->datasource->process($datagrid, $config);
        $this->datasource = clone $this->datasource;

        $this->assertEquals($qb, $this->datasource->getQueryBuilder());
        $this->assertNotSame($qb, $this->datasource->getQueryBuilder());

        $this->assertEquals($countQb, $this->datasource->getCountQb());
        $this->assertNotSame($countQb, $this->datasource->getCountQb());
    }

    public function testCloneWithoutCountQueryBuilder()
    {
        $config = $this->getConfig();
        $datagrid = $this->createMock(DatagridInterface::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $qb = new QueryBuilder($em);
        $qb->from(SomeClass::class, 't')->select('t');

        $this->processor->expects($this->once())
            ->method('processQuery')
            ->willReturn($qb);
        $this->processor->expects($this->once())
            ->method('processCountQuery')
            ->willReturn(null);

        $this->datasource->process($datagrid, $config);
        $this->datasource = clone $this->datasource;

        $this->assertEquals($qb, $this->datasource->getQueryBuilder());
        $this->assertNotSame($qb, $this->datasource->getQueryBuilder());

        $this->assertNull($this->datasource->getCountQb());
    }
}
