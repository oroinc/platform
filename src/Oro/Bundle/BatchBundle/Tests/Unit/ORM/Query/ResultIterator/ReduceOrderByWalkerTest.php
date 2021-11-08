<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\ResultIterator;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\AST\OrderByItem;
use Doctrine\ORM\Query\AST\PathExpression;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\ReduceOrderByWalker;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\ORM\Query\ResultIterator\SqlWalkerHelperTrait;

class ReduceOrderByWalkerTest extends \PHPUnit\Framework\TestCase
{
    use SqlWalkerHelperTrait;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var ReduceOrderByWalker */
    private $reduceOrderByWalker;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->reduceOrderByWalker = new ReduceOrderByWalker(
            $query,
            null,
            $this->getQueryComponents()
        );
    }

    public function testWalkSelectStatementShouldRemoveUnnecessaryOrderBy()
    {
        $ast = $this->getDefaultAST();
        $ast->orderByClause = new OrderByClause(
            [
                new OrderByItem(
                    new PathExpression(
                        PathExpression::TYPE_STATE_FIELD,
                        'email',
                        'email'
                    )
                ),
                new OrderByItem(
                    new PathExpression(
                        PathExpression::TYPE_STATE_FIELD,
                        'o',
                        'o'
                    )
                ),
            ]
        );

        $this->reduceOrderByWalker->walkSelectStatement($ast);
        $this->assertCount(1, $ast->orderByClause->orderByItems);
    }

    public function testWalkShouldSkipIfThereIsNoOrderBy()
    {
        $ast = $this->getDefaultAST();

        $this->reduceOrderByWalker->walkSelectStatement($ast);

        $this->assertNull($ast->orderByClause);
    }
}
