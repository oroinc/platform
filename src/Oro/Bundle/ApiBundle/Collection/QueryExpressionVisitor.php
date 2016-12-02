<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Query\QueryException;

use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\ComparisonExpressionInterface;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\CompositeExpressionInterface;

/**
 * This expression visitor was created to be able to add custom composite and comparison expressions
 */
class QueryExpressionVisitor extends ExpressionVisitor
{
    /** @var array */
    private $queryAliases;

    /** @var array */
    private $parameters = [];

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
     * @param array $queryAliases
     */
    public function setQueryAliases(array $queryAliases)
    {
        $this->queryAliases = $queryAliases;
    }

    /**
     * Gets bound parameters.
     * Filled after {@link dispach()}.
     *
     * @return ArrayCollection
     */
    public function getParameters()
    {
        return new ArrayCollection($this->parameters);
    }

    /**
     * Add new parameter.
     *
     * @param mixed $value
     */
    public function addParameter($value)
    {
        $this->parameters[] = $value;
    }

    /**
     * Build placeholder string for given parameter name.
     *
     * @param $parameterName
     *
     * @return string
     */
    public function buildPlaceholder($parameterName)
    {
        return ':' . $parameterName;
    }

    /**
     * {@inheritDoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = [];

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        $expressionType = $expr->getType();

        if (array_key_exists($expressionType, $this->compositeExpressions)) {
            return $this->compositeExpressions[$expressionType]->walkCompositeExpression($expressionList);
        }

        throw new QueryException('Unknown composite ' . $expr->getType());
    }

    /**
     * {@inheritDoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        if (!isset($this->queryAliases[0])) {
            throw new QueryException('No aliases are set before invoking walkComparison().');
        }

        $field = $this->queryAliases[0] . '.' . $comparison->getField();

        foreach ($this->queryAliases as $alias) {
            if (strpos($comparison->getField() . '.', $alias . '.') === 0) {
                $field = $comparison->getField();
                break;
            }
        }

        $parameterName = str_replace('.', '_', $comparison->getField());

        foreach ($this->parameters as $parameter) {
            if ($parameter->getName() === $parameterName) {
                $parameterName .= '_' . count($this->parameters);
                break;
            }
        }

        // try to find the comparison expression by operator.
        $operator = $comparison->getOperator();
        if (array_key_exists($operator, $this->comparisonExpressions)) {
            return $this->comparisonExpressions[$operator]->walkComparisonExpression(
                $this,
                $comparison,
                $parameterName,
                $field
            );
        }

        throw new QueryException("Unknown comparison operator: " . $comparison->getOperator());
    }

    /**
     * {@inheritDoc}
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }
}
