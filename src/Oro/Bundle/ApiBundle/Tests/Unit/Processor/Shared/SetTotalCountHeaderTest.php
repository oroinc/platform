<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\SetTotalCountHeader;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;
use Oro\Component\EntitySerializer\QueryResolver;

class SetTotalCountHeaderTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|CountQueryBuilderOptimizer */
    private $countQueryBuilderOptimizer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryResolver */
    private $queryResolver;

    /** @var SetTotalCountHeader */
    private $processor;

    protected function setUp()
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

    public function testProcessWithWrongTotalCallback()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected callable for "totalCount", "stdClass" given.');

        $this->context->setTotalCountCallback(new \stdClass());
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->processor->process($this->context);
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

    public function testProcessQueryBuilder()
    {
        $entityClass = Group::class;
        $config = new EntityDefinitionConfig();
        $totalCount = 123;

        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');

        $this->countQueryBuilderOptimizer->expects(self::once())
            ->method('getCountQueryBuilder')
            ->willReturnCallback(
                function (QueryBuilder $qb) {
                    $qb->setMaxResults(10);

                    return $qb;
                }
            );
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

    public function testProcessQuery()
    {
        $entityClass = Group::class;
        $config = new EntityDefinitionConfig();
        $totalCount = 123;

        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');

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

    public function testProcessOnWrongQuery()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Expected instance of Doctrine\ORM\QueryBuilder, Doctrine\ORM\Query, '
            . 'Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder or Oro\Bundle\EntityBundle\ORM\SqlQuery, '
            . '"stdClass" given.'
        );

        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->context->setQuery(new \stdClass());
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);
    }
}
