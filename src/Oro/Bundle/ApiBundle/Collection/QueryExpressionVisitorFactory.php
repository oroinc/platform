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
     * @param array $compositeExpressions
     * @param array $comparisonExpressions
     */
    public function __construct($compositeExpressions = [], $comparisonExpressions = [])
    {
        $this->compositeExpressions = $compositeExpressions;
        $this->comparisonExpressions = $comparisonExpressions;
    }

    /**
     * Returns new instance of QueryExpressionVisitor.
     *
     * @return QueryExpressionVisitor
     */
    public function getExpressionVisitor()
    {
        return new QueryExpressionVisitor($this->compositeExpressions, $this->comparisonExpressions);
    }
}
