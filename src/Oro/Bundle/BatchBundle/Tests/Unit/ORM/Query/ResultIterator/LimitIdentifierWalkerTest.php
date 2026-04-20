<?php

declare(strict_types=1);

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\ResultIterator;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\ParserResult;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\LimitIdentifierWalker;
use Oro\Bundle\EntityBundle\ORM\Query\AST\AnyExpression;
use PHPUnit\Framework\TestCase;

class LimitIdentifierWalkerTest extends TestCase
{
    private LimitIdentifierWalker $walker;

    #[\Override]
    protected function setUp(): void
    {
        $rootMetadata = new ClassMetadata('Entity\Test');
        $rootMetadata->setIdentifier(['id']);
        $rootMetadata->mapField(['fieldName' => 'id', 'columnName' => 'id']);

        $this->walker = new LimitIdentifierWalker(
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

    public function testWalkSelectStatementCreatesWhereClauseWithAnyExpression(): void
    {
        $ast = $this->getAst();

        $this->walker->walkSelectStatement($ast);

        // Verify that a WHERE clause was created with the expected structure
        $this->assertNotNull($ast->whereClause);
        $this->assertInstanceOf(WhereClause::class, $ast->whereClause);
        $this->assertInstanceOf(
            ConditionalExpression::class,
            $ast->whereClause->conditionalExpression
        );

        // Navigate to the ComparisonExpression to verify the ANY function is used
        $terms = $ast->whereClause->conditionalExpression->conditionalTerms;
        $this->assertCount(1, $terms);

        $factors = $terms[0]->conditionalFactors;
        $this->assertCount(1, $factors);

        $simpleExpr = $factors[0]->simpleConditionalExpression;
        $this->assertInstanceOf(ComparisonExpression::class, $simpleExpr);
        $this->assertInstanceOf(AnyExpression::class, $simpleExpr->rightExpression);
        $this->assertEquals('=', $simpleExpr->operator);
    }

    public function testWalkSelectStatementWithExistingConditionalTermWhereClause(): void
    {
        $ast = $this->getAst();

        // Add existing WHERE clause with ConditionalTerm (which gets the new condition appended)
        $existingCondition = new ConditionalPrimary();
        $existingCondition->simpleConditionalExpression = new ComparisonExpression(
            null,
            '=',
            null
        );

        $conditionalTerm = new ConditionalTerm([$existingCondition]);
        $ast->whereClause = new WhereClause($conditionalTerm);

        $this->walker->walkSelectStatement($ast);

        // Should still be a ConditionalTerm with the original + new ANY condition
        $this->assertInstanceOf(ConditionalTerm::class, $ast->whereClause->conditionalExpression);
        $this->assertCount(2, $ast->whereClause->conditionalExpression->conditionalFactors);
    }

    public function testWalkSelectStatementWithExistingConditionalPrimaryWhereClause(): void
    {
        $ast = $this->getAst();

        // Add existing WHERE clause with a ConditionalPrimary
        $existingPrimary = new ConditionalPrimary();
        $existingPrimary->simpleConditionalExpression = new ComparisonExpression(
            null,
            '=',
            null
        );
        $ast->whereClause = new WhereClause($existingPrimary);

        $this->walker->walkSelectStatement($ast);

        // Should be wrapped in a ConditionalExpression containing a single ConditionalTerm with 2 factors
        $this->assertInstanceOf(
            ConditionalExpression::class,
            $ast->whereClause->conditionalExpression
        );
        $terms = $ast->whereClause->conditionalExpression->conditionalTerms;
        $this->assertCount(1, $terms);
        $this->assertCount(2, $terms[0]->conditionalFactors);
    }

    public function testWalkSelectStatementWithExistingConditionalExpressionWhereClause(): void
    {
        $ast = $this->getAst();

        // Add existing WHERE clause with a ConditionalExpression (e.g. from an OR condition)
        $primary = new ConditionalPrimary();
        $primary->simpleConditionalExpression = new ComparisonExpression(null, '=', null);
        $existingExpression = new ConditionalExpression([
            new ConditionalTerm([$primary])
        ]);
        $ast->whereClause = new WhereClause($existingExpression);

        $this->walker->walkSelectStatement($ast);

        // Should be converted to a ConditionalTerm wrapping the original expression + the new condition
        $this->assertInstanceOf(ConditionalTerm::class, $ast->whereClause->conditionalExpression);
        $this->assertCount(2, $ast->whereClause->conditionalExpression->conditionalFactors);
    }

    public function testMultipleFromClausesThrowException(): void
    {
        // Setup walker with multiple identifications
        $ast = $this->getAst();

        // Add another from clause element (simulating multiple FROM clauses)
        $ast->fromClause->identificationVariableDeclarations[] =
            new IdentificationVariableDeclaration(new RangeVariableDeclaration('Test2', 'e2'), null, []);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is more then 1 From clause');

        $this->walker->walkSelectStatement($ast);
    }
}
