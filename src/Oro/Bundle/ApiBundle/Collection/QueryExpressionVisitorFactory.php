<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\ComparisonExpressionInterface;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\CompositeExpressionInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

/**
 * The factory to create a new instance of QueryExpressionVisitor class.
 */
class QueryExpressionVisitorFactory
{
    /** @var CompositeExpressionInterface[] */
    private array $compositeExpressions;
    /** @var ComparisonExpressionInterface[] */
    private array $comparisonExpressions;
    private EntityClassResolver $entityClassResolver;

    /**
     * @param CompositeExpressionInterface[]  $compositeExpressions  [type => expression, ...]
     * @param ComparisonExpressionInterface[] $comparisonExpressions [operator => expression, ...]
     * @param EntityClassResolver             $entityClassResolver
     */
    public function __construct(
        array $compositeExpressions,
        array $comparisonExpressions,
        EntityClassResolver $entityClassResolver
    ) {
        $this->compositeExpressions = $compositeExpressions;
        $this->comparisonExpressions = $comparisonExpressions;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * Creates a new instance of QueryExpressionVisitor.
     */
    public function createExpressionVisitor(): QueryExpressionVisitor
    {
        return new QueryExpressionVisitor(
            $this->compositeExpressions,
            $this->comparisonExpressions,
            $this->entityClassResolver
        );
    }
}
