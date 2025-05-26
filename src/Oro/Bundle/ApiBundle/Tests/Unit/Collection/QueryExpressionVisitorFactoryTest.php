<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitorFactory;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\ComparisonExpressionInterface;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\CompositeExpressionInterface;
use Oro\Bundle\ApiBundle\Util\FieldDqlExpressionProviderInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use PHPUnit\Framework\TestCase;

class QueryExpressionVisitorFactoryTest extends TestCase
{
    public function testCreateExpressionVisitor(): void
    {
        $compositeExpressions = ['AND' => $this->createMock(CompositeExpressionInterface::class)];
        $comparisonExpressions = ['=' => $this->createMock(ComparisonExpressionInterface::class)];
        $fieldDqlExpressionProvider = $this->createMock(FieldDqlExpressionProviderInterface::class);
        $entityClassResolver = $this->createMock(EntityClassResolver::class);

        $factory = new QueryExpressionVisitorFactory(
            $compositeExpressions,
            $comparisonExpressions,
            $fieldDqlExpressionProvider,
            $entityClassResolver
        );

        $expressionVisitor = $factory->createExpressionVisitor();

        self::assertEquals(
            new QueryExpressionVisitor(
                $compositeExpressions,
                $comparisonExpressions,
                $fieldDqlExpressionProvider,
                $entityClassResolver
            ),
            $expressionVisitor
        );
    }
}
