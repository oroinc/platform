<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\ResultIterator;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\AST\OrderByItem;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\ParserResult;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\ReduceOrderByWalker;

class ReduceOrderByWalkerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ReduceOrderByWalker */
    private $reduceOrderByWalker;

    protected function setUp(): void
    {
        $rootMetadata = new ClassMetadata('Entity\Root');
        $rootMetadata->setIdentifier(['e']);

        $this->reduceOrderByWalker = new ReduceOrderByWalker(
            $this->createMock(AbstractQuery::class),
            $this->createMock(ParserResult::class),
            ['e' => ['map' => null, 'metadata' => $rootMetadata]]
        );
    }

    private function getAst(): SelectStatement
    {
        return new SelectStatement(
            new SelectClause([new SelectExpression('e', 'id', null)], false),
            new FromClause([
                new IdentificationVariableDeclaration(new RangeVariableDeclaration('Test', 'e'), null, [])
            ])
        );
    }

    public function testWalkSelectStatementShouldRemoveUnnecessaryOrderBy(): void
    {
        $ast = $this->getAst();
        $ast->orderByClause = new OrderByClause([
            new OrderByItem(new PathExpression(PathExpression::TYPE_STATE_FIELD, 'email', 'email')),
            new OrderByItem(new PathExpression(PathExpression::TYPE_STATE_FIELD, 'e', 'e')),
        ]);

        $this->reduceOrderByWalker->walkSelectStatement($ast);

        $this->assertCount(1, $ast->orderByClause->orderByItems);
    }

    public function testWalkShouldSkipIfThereIsNoOrderBy(): void
    {
        $ast = $this->getAst();

        $this->reduceOrderByWalker->walkSelectStatement($ast);

        $this->assertNull($ast->orderByClause);
    }
}
