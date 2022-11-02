<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Search;

use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\Search\SearchAclHelperConditionProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Search\TestSearchAclHelperCondition;

class SearchAclHelperConditionProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testIsApplicable()
    {
        $conditionProvider = new SearchAclHelperConditionProvider([
            new TestSearchAclHelperCondition(
                function ($className, $permission) {
                    return \stdClass::class === $className && 'VIEW' === $permission;
                },
                function ($query, $alias, $orExpression) {
                    throw new \Exception('Not expected to be called.');
                }
            ),
            new TestSearchAclHelperCondition(
                function ($className, $permission) {
                    return 'Some\Other\Class' === $className && 'VIEW' === $permission;
                },
                function ($query, $alias, $orExpression) {
                    throw new \Exception('Not expected to be called.');
                }
            )
        ]);

        self::assertTrue($conditionProvider->isApplicable(\stdClass::class, 'VIEW'));
        self::assertFalse($conditionProvider->isApplicable(\stdClass::class, 'EDIT'));
        self::assertTrue($conditionProvider->isApplicable('Some\Other\Class', 'VIEW'));
        self::assertFalse($conditionProvider->isApplicable('Some\Other\Class', 'EDIT'));
        self::assertFalse($conditionProvider->isApplicable('Acme\Other\Class', 'VIEW'));
    }

    public function testAddRestrictionOnEmptyAclConditions(): void
    {
        $query = new Query();
        $conditionProvider = new SearchAclHelperConditionProvider([]);

        self::assertNull($conditionProvider->addRestriction($query, \stdClass::class, 'VIEW', 'std'));
    }

    public function testAddRestrictionOnNonSupportedProviders(): void
    {
        $query = new Query();
        $conditionProvider = new SearchAclHelperConditionProvider([
            new TestSearchAclHelperCondition(
                function ($className, $permission) {
                    self::assertEquals(\stdClass::class, $className);
                    self::assertEquals('VIEW', $permission);

                    return false;
                },
                function ($query, $alias, $orExpression) {
                    throw new \Exception('Not expected to be called.');
                }
            ),
            new TestSearchAclHelperCondition(
                function ($className, $permission) {
                    self::assertEquals(\stdClass::class, $className);
                    self::assertEquals('VIEW', $permission);

                    return false;
                },
                function ($query, $alias, $orExpression) {
                    throw new \Exception('Not expected to be called.');
                }
            )
        ]);

        self::assertNull($conditionProvider->addRestriction($query, \stdClass::class, 'VIEW', 'std'));
    }


    public function testAddRestriction(): void
    {
        $query = new Query();
        $expressionBuilder = new ExpressionBuilder();
        $expectedExpression = $expressionBuilder->eq('test', 'value');

        $conditionProvider = new SearchAclHelperConditionProvider([
            new TestSearchAclHelperCondition(
                function ($className, $permission) {
                    self::assertEquals(\stdClass::class, $className);
                    self::assertEquals('VIEW', $permission);

                    return true;
                },
                function ($query, $alias, $orExpression) use ($expectedExpression) {
                    self::assertNull($orExpression);
                    self::assertEquals('std', $alias);
                    return $expectedExpression;
                }
            ),
            new TestSearchAclHelperCondition(
                function ($className, $permission) {
                    self::assertEquals(\stdClass::class, $className);
                    self::assertEquals('VIEW', $permission);

                    return true;
                },
                function ($query, $alias, $orExpression) use ($expectedExpression) {
                    self::assertSame($expectedExpression, $orExpression);
                    self::assertEquals('std', $alias);
                    return $expectedExpression;
                }
            )
        ]);

        self::assertSame(
            $expectedExpression,
            $conditionProvider->addRestriction($query, \stdClass::class, 'VIEW', 'std')
        );
    }
}
