<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\ComparisonExpressionInterface;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\CompositeExpressionInterface;

class QueryExpressionVisitorFactory
{
    /** @var CompositeExpressionInterface[] */
    private $compositeExpressions = [];

    /** @var ComparisonExpressionInterface[] */
    private $comparisonExpressions = [];

    /**
     * @param CompositeExpressionInterface[]  $compositeExpressions  [type => expression, ...]
     * @param ComparisonExpressionInterface[] $comparisonExpressions [operator => expression, ...]
     */
    public function __construct(array $compositeExpressions = [], array $comparisonExpressions = [])
    {
        $this->compositeExpressions = $compositeExpressions;
        $this->comparisonExpressions = $comparisonExpressions;
    }

    /**
     * Creates a new instance of QueryExpressionVisitor.
     *
     * @return QueryExpressionVisitor
     */
    public function createExpressionVisitor()
    {
        return new QueryExpressionVisitor($this->compositeExpressions, $this->comparisonExpressions);
    }
}
