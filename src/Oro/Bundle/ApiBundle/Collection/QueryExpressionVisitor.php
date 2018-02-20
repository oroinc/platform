<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\Query\Expr as ExpressionBuilder;
use Doctrine\ORM\Query\Parameter;
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

    /** @var Parameter[] */
    private $parameters = [];

    /** @var ExpressionBuilder */
    private $expressionBuilder;

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
     * @param array $queryAliases
     */
    public function setQueryAliases(array $queryAliases)
    {
        $this->queryAliases = $queryAliases;
    }

    /**
     * Gets bound parameters.
     *
     * @return Parameter[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Binds a new parameter.
     *
     * @param Parameter|string $parameter An instance of Parameter object or the name of a parameter
     * @param mixed            $value     The value of a parameter
     * @param mixed            $type      The data type of a parameter
     */
    public function addParameter($parameter, $value = null, $type = null)
    {
        if (!$parameter instanceof Parameter) {
            $parameter = $this->createParameter($parameter, $value, $type);
        }
        $this->parameters[] = $parameter;
    }

    /**
     * Creates a new instance of Parameter.
     *
     * @param string $name
     * @param mixed  $value
     * @param mixed  $type
     *
     * @return Parameter
     */
    public function createParameter($name, $value, $type = null)
    {
        return new Parameter($name, $value, $type);
    }

    /**
     * Builds placeholder string for given parameter name.
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
     * Gets a builder that can be used to create a different kind of expressions.
     *
     * @return ExpressionBuilder
     */
    public function getExpressionBuilder()
    {
        if (null === $this->expressionBuilder) {
            $this->expressionBuilder = new ExpressionBuilder();
        }

        return $this->expressionBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionType = $expr->getType();
        if (!isset($this->compositeExpressions[$expressionType])) {
            throw new QueryException('Unknown composite ' . $expr->getType());
        }

        $processedExpressions = [];
        $expressions = $expr->getExpressionList();
        foreach ($expressions as $expression) {
            $processedExpressions[] = $this->dispatch($expression);
        }

        return $this->compositeExpressions[$expressionType]
            ->walkCompositeExpression($processedExpressions);
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        if (!isset($this->queryAliases[0])) {
            throw new QueryException('No aliases are set before invoking walkComparison().');
        }

        $operator = $comparison->getOperator();
        if (!isset($this->comparisonExpressions[$operator])) {
            throw new QueryException('Unknown comparison operator: ' . $comparison->getOperator());
        }

        return $this->comparisonExpressions[$operator]
            ->walkComparisonExpression(
                $this,
                $comparison,
                $this->getFieldName($comparison->getField()),
                $this->getParameterName($comparison->getField())
            );
    }

    /**
     * {@inheritdoc}
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    private function getFieldName($fieldName)
    {
        $result = $this->queryAliases[0] . '.' . $fieldName;
        foreach ($this->queryAliases as $alias) {
            if (0 === strpos($fieldName . '.', $alias . '.')) {
                $result = $fieldName;
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    private function getParameterName($fieldName)
    {
        $result = str_replace('.', '_', $fieldName);
        foreach ($this->parameters as $parameter) {
            if ($parameter->getName() === $result) {
                $result .= '_' . count($this->parameters);
                break;
            }
        }

        return $result;
    }
}
