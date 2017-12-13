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

class ReduceOrderByWalkerTest extends \PHPUnit_Framework_TestCase
{
    use SqlWalkerHelperTrait;

    /**
     * @var AbstractQuery|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $query;

    /**
     * @var |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parserResult;

    /**
     * @var |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryComponents = [];

    /**
     * @var ReduceOrderByWalker
     */
    protected $reduceOrderByWalker;

    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connection;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    protected function setUp()
    {
        $this->query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->query->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->reduceOrderByWalker = new ReduceOrderByWalker(
            $this->query,
            $this->parserResult,
            $this->getQueryComponents()
        );
    }

    public function testWalkSelectStatementShouldRemoveUnnecesseryOrderBy()
    {
        $AST = $this->getDefaultAST();
        $AST->orderByClause = new OrderByClause(
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

        $this->reduceOrderByWalker->walkSelectStatement($AST);
        $this->assertCount(1, $AST->orderByClause->orderByItems);
    }

    public function testWalkShouldSkipIfThereIsNoOrderBy()
    {
        $AST = $this->getDefaultAST();

        $this->reduceOrderByWalker->walkSelectStatement($AST);

        $this->assertNull($AST->orderByClause);
    }
}
