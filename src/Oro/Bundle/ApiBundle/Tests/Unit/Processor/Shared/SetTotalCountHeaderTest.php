<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\SetTotalCountHeader;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;
use Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQuery;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;
use Oro\Component\EntitySerializer\QueryResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SetTotalCountHeaderTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|CountQueryBuilderOptimizer */
    private $countQueryBuilderOptimizer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryResolver */
    private $queryResolver;

    /** @var SetTotalCountHeader */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->countQueryBuilderOptimizer = $this->createMock(CountQueryBuilderOptimizer::class);
        $this->queryResolver = $this->createMock(QueryResolver::class);

        $this->processor = new SetTotalCountHeader($this->countQueryBuilderOptimizer, $this->queryResolver);
    }

    public function testProcessWithoutRequestHeader()
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('X-Include-Total-Count'));
    }

    public function testProcessOnExistingHeader()
    {
        $testCount = 135;

        $this->context->getResponseHeaders()->set('X-Include-Total-Count', $testCount);
        $this->processor->process($this->context);

        self::assertEquals(
            $testCount,
            $this->context->getResponseHeaders()->get('X-Include-Total-Count')
        );
    }

    public function testProcessWithTotalCallback()
    {
        $testCount = 135;

        $this->context->setTotalCountCallback(
            function () use ($testCount) {
                return $testCount;
            }
        );
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->processor->process($this->context);

        self::assertEquals(
            $testCount,
            $this->context->getResponseHeaders()->get('X-Include-Total-Count')
        );
    }

    public function testProcessWithWrongTotalCallbackResult()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected integer as result of "totalCount" callback, "string" given.');

        $this->context->setTotalCountCallback(
            function () {
                return 'non integer value';
            }
        );
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->processor->process($this->context);
    }

    public function testProcessOrmQueryBuilder()
    {
        $entityClass = Group::class;
        $config = new EntityDefinitionConfig();
        $totalCount = 123;

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');
        $query->setFirstResult(20);
        $query->setMaxResults(10);

        $this->countQueryBuilderOptimizer->expects(self::once())
            ->method('getCountQueryBuilder')
            ->willReturnCallback(function (QueryBuilder $qb) {
                $qb->select('e.id');

                return $qb;
            });
        $this->queryResolver->expects(self::once())
            ->method('resolveQuery')
            ->with(self::isInstanceOf(Query::class), self::identicalTo($config));
        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT count(DISTINCT g0_.id) AS sclr_0 FROM group_table g0_',
            [['sclr_0' => $totalCount]]
        );

        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->context->setQuery($query);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertEquals(
            $totalCount,
            $this->context->getResponseHeaders()->get('X-Include-Total-Count')
        );
    }

    public function testProcessOrmQuery()
    {
        $entityClass = Group::class;
        $config = new EntityDefinitionConfig();
        $totalCount = 123;

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');
        $query->setFirstResult(20);
        $query->setMaxResults(10);

        $this->queryResolver->expects(self::once())
            ->method('resolveQuery')
            ->with(self::isInstanceOf(Query::class), self::identicalTo($config));
        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT count(DISTINCT g0_.id) AS sclr_0 FROM group_table g0_',
            [['sclr_0' => $totalCount]]
        );

        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->context->setQuery($query->getQuery());
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertEquals(
            $totalCount,
            $this->context->getResponseHeaders()->get('X-Include-Total-Count')
        );
    }

    public function testProcessSqlQueryBuilder()
    {
        $config = new EntityDefinitionConfig();
        $totalCount = 123;

        $rsm = ResultSetMappingUtil::createResultSetMapping($this->em->getConnection()->getDatabasePlatform());
        $rsm
            ->addScalarResult('id', 'id')
            ->addScalarResult('name', 'name');
        $qb = new SqlQueryBuilder($this->em, $rsm);
        $qb
            ->select('e.id AS id, e.name AS name')
            ->from('group_table', 'e')
            ->setFirstResult(20)
            ->setMaxResults(10);

        $this->queryResolver->expects(self::never())
            ->method('resolveQuery');
        $this->getDriverConnectionMock($this->em)->expects(self::once())
            ->method('query')
            ->willReturnCallback(function ($sql) use ($totalCount) {
                self::assertEquals(
                    'SELECT COUNT(*)'
                    . ' FROM (SELECT e.id AS id, e.name AS name FROM group_table e) count_query',
                    $sql
                );

                return $this->createCountStatementMock($totalCount);
            });

        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->context->setQuery($qb);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertEquals(
            $totalCount,
            $this->context->getResponseHeaders()->get('X-Include-Total-Count')
        );
    }

    public function testProcessSqlQuery()
    {
        $config = new EntityDefinitionConfig();
        $totalCount = 123;

        /** @var Query\ResultSetMapping $rsm */
        $rsm = $this->createMock(Query\ResultSetMapping::class);
        $qb = new SqlQueryBuilder($this->em, $rsm);
        $qb
            ->select('e.id AS id, e.name AS name')
            ->from('group_table', 'e')
            ->setFirstResult(20)
            ->setMaxResults(10);
        $query = new SqlQuery($this->em);
        $query->setSqlQueryBuilder($qb);

        $this->queryResolver->expects(self::never())
            ->method('resolveQuery');
        $this->getDriverConnectionMock($this->em)->expects(self::once())
            ->method('query')
            ->willReturnCallback(function ($sql) use ($totalCount) {
                self::assertEquals(
                    'SELECT COUNT(*)'
                    . ' FROM (SELECT e.id AS id, e.name AS name FROM group_table e) count_query',
                    $sql
                );

                return $this->createCountStatementMock($totalCount);
            });

        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->context->setQuery($query);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertEquals(
            $totalCount,
            $this->context->getResponseHeaders()->get('X-Include-Total-Count')
        );
    }

    public function testProcessOnWrongQuery()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Expected instance of Doctrine\ORM\QueryBuilder, Doctrine\ORM\Query, '
            . 'Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder or Oro\Component\DoctrineUtils\ORM\SqlQuery, '
            . '"stdClass" given.'
        );

        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->context->setQuery(new \stdClass());
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);
    }

    public function testArrayData()
    {
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->context->setResult([['id' => 1], ['id' => 2]]);
        $this->processor->process($this->context);

        self::assertEquals(
            2,
            $this->context->getResponseHeaders()->get('X-Include-Total-Count')
        );
    }

    public function testObjectData()
    {
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);

        self::assertFalse(
            $this->context->getResponseHeaders()->has('X-Include-Total-Count')
        );
    }

    public function testNoData()
    {
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->processor->process($this->context);

        self::assertFalse(
            $this->context->getResponseHeaders()->has('X-Include-Total-Count')
        );
    }
}
