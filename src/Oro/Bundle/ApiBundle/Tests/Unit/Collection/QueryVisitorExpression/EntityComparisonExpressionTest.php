<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr as CollectionExpr;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\EntityComparisonExpression;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class EntityComparisonExpressionTest extends OrmRelatedTestCase
{
    /**
     * @dataProvider walkComparisonExpressionDataProvider
     */
    public function testWalkComparisonExpression(
        string $comparisonType,
        mixed $comparisonValue,
        string $expectedSubqueryWhereExpr,
        array $expectedSubqueryParameters
    ): void {
        $expression = new EntityComparisonExpression($this->doctrine);
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = $field;
        $parameterName = 'group_1';
        $value = [Entity\Group::class, new CollectionExpr\Comparison('name', $comparisonType, $comparisonValue)];

        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryAliases(['e']);
        $expressionVisitor->setQueryJoinMap([]);

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        $expectedSubquery = 'SELECT e_subquery1.id'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group e_subquery1'
            . ' WHERE ' . $expectedSubqueryWhereExpr;

        self::assertEquals(
            new Func('e.groups IN', $expectedSubquery),
            $result
        );
        self::assertEquals(
            $expectedSubqueryParameters,
            $expressionVisitor->getParameters()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function walkComparisonExpressionDataProvider(): array
    {
        return [
            'EQ' => [
                CollectionExpr\Comparison::EQ,
                'some name',
                'e_subquery1.name = :group_1__1',
                [
                    new Parameter('group_1__1', 'some name')
                ]
            ],
            'EQ with NULL value' => [
                CollectionExpr\Comparison::EQ,
                null,
                'e_subquery1.name IS NULL',
                []
            ],
            'NEQ' => [
                CollectionExpr\Comparison::NEQ,
                'some name',
                'e_subquery1.name <> :group_1__1',
                [
                    new Parameter('group_1__1', 'some name')
                ]
            ],
            'NEQ with NULL value' => [
                CollectionExpr\Comparison::NEQ,
                null,
                'e_subquery1.name IS NOT NULL',
                []
            ],
            'IN' => [
                CollectionExpr\Comparison::IN,
                ['some name 1', 'some name 2'],
                'e_subquery1.name IN(:group_1__1)',
                [
                    new Parameter('group_1__1', ['some name 1', 'some name 2'])
                ]
            ],
            'NIN' => [
                CollectionExpr\Comparison::NIN,
                ['some name 1', 'some name 2'],
                'e_subquery1.name NOT IN(:group_1__1)',
                [
                    new Parameter('group_1__1', ['some name 1', 'some name 2'])
                ]
            ],
            'CONTAINS' => [
                CollectionExpr\Comparison::CONTAINS,
                'some name',
                'e_subquery1.name LIKE :group_1__1',
                [
                    new Parameter('group_1__1', '%some name%')
                ]
            ],
            'STARTS_WITH' => [
                CollectionExpr\Comparison::STARTS_WITH,
                'some name',
                'e_subquery1.name LIKE :group_1__1',
                [
                    new Parameter('group_1__1', 'some name%')
                ]
            ],
            'ENDS_WITH' => [
                CollectionExpr\Comparison::ENDS_WITH,
                'some name',
                'e_subquery1.name LIKE :group_1__1',
                [
                    new Parameter('group_1__1', '%some name')
                ]
            ],
            'GT' => [
                CollectionExpr\Comparison::GT,
                'some name',
                'e_subquery1.name > :group_1__1',
                [
                    new Parameter('group_1__1', 'some name')
                ]
            ],
            'GTE' => [
                CollectionExpr\Comparison::GTE,
                'some name',
                'e_subquery1.name >= :group_1__1',
                [
                    new Parameter('group_1__1', 'some name')
                ]
            ],
            'LT' => [
                CollectionExpr\Comparison::LT,
                'some name',
                'e_subquery1.name < :group_1__1',
                [
                    new Parameter('group_1__1', 'some name')
                ]
            ],
            'LTE' => [
                CollectionExpr\Comparison::LTE,
                'some name',
                'e_subquery1.name <= :group_1__1',
                [
                    new Parameter('group_1__1', 'some name')
                ]
            ]
        ];
    }

    public function testWalkComparisonExpressionForUnknownComparisonType(): void
    {
        $expression = new EntityComparisonExpression($this->doctrine);
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryAliases(['e']);
        $expressionVisitor->setQueryJoinMap([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown comparison operator: UNKNOWN.');

        $expression->walkComparisonExpression(
            $expressionVisitor,
            'e.groups',
            'e.groups',
            'group_1',
            [Entity\Group::class, new CollectionExpr\Comparison('name', 'UNKNOWN', 'some name')]
        );
    }

    public function testWalkComparisonExpressionForComplexExpression(): void
    {
        $expression = new EntityComparisonExpression($this->doctrine);
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = $field;
        $parameterName = 'group_1';
        $value = [
            Entity\Group::class,
            new CollectionExpr\CompositeExpression(
                CollectionExpr\CompositeExpression::TYPE_AND,
                [
                    new CollectionExpr\Comparison('name', CollectionExpr\Comparison::STARTS_WITH, 'start'),
                    new CollectionExpr\Comparison('name', CollectionExpr\Comparison::ENDS_WITH, 'end')
                ]
            )
        ];

        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryAliases(['e']);
        $expressionVisitor->setQueryJoinMap([]);

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        $expectedSubquery = 'SELECT e_subquery1.id'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group e_subquery1'
            . ' WHERE e_subquery1.name LIKE :group_1__1 AND e_subquery1.name LIKE :group_1__2';

        self::assertEquals(
            new Func('e.groups IN', $expectedSubquery),
            $result
        );
        self::assertEquals(
            [
                new Parameter('group_1__1', 'start%'),
                new Parameter('group_1__2', '%end')
            ],
            $expressionVisitor->getParameters()
        );
    }
}
