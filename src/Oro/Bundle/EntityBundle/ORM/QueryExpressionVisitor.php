<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;

/**
 * fix of doctrine error with Same Field, Multiple Values, Criteria and QueryBuilder
 * http://www.doctrine-project.org/jira/browse/DDC-2798
 * TODO remove this file when doctrine version >= 2.5 in scope of BAP-5577
 */
class QueryExpressionVisitor extends ExpressionVisitor
{
    /**
     * @var array
     */
    private static $operatorMap = array(
        Comparison::GT => Expr\Comparison::GT,
        Comparison::GTE => Expr\Comparison::GTE,
        Comparison::LT  => Expr\Comparison::LT,
        Comparison::LTE => Expr\Comparison::LTE
    );

    /**
     * @var string
     */
    private $rootAlias;

    /**
     * @var Expr
     */
    private $expr;

    /**
     * @var array
     */
    private $parameters = array();

    /**
     * Constructor
     *
     * @param string $rootAlias
     */
    public function __construct($rootAlias)
    {
        $this->rootAlias = $rootAlias;
        $this->expr = new Expr();
    }

    /**
     * Gets bound parameters.
     * Filled after {@link dispach()}.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParameters()
    {
        return new ArrayCollection($this->parameters);
    }

    /**
     * Clears parameters.
     *
     * @return void
     */
    public function clearParameters()
    {
        $this->parameters = array();
    }

    /**
     * Converts Criteria expression to Query one based on static map.
     *
     * @param string $criteriaOperator
     *
     * @return string|null
     */
    private static function convertComparisonOperator($criteriaOperator)
    {
        return isset(self::$operatorMap[$criteriaOperator]) ? self::$operatorMap[$criteriaOperator] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = array();

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        switch($expr->getType()) {
            case CompositeExpression::TYPE_AND:
                return new Expr\Andx($expressionList);

            case CompositeExpression::TYPE_OR:
                return new Expr\Orx($expressionList);

            default:
                throw new \RuntimeException("Unknown composite " . $expr->getType());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        $parameterName = str_replace('.', '_', $comparison->getField());

        foreach ($this->parameters as $parameter) {
            if ($parameter->getName() === $parameterName) {
                $parameterName .= '_' . count($this->parameters);
                break;
            }
        }

        $parameter = new Parameter($parameterName, $this->walkValue($comparison->getValue()));
        $placeholder = ':' . $parameterName;

        switch ($comparison->getOperator()) {
            case Comparison::IN:
                $this->parameters[] = $parameter;
                return $this->expr->in($this->rootAlias . '.' . $comparison->getField(), $placeholder);

            case Comparison::NIN:
                $this->parameters[] = $parameter;
                return $this->expr->notIn($this->rootAlias . '.' . $comparison->getField(), $placeholder);

            case Comparison::EQ:
            case Comparison::IS:
                if ($this->walkValue($comparison->getValue()) === null) {
                    return $this->expr->isNull($this->rootAlias . '.' . $comparison->getField());
                }
                $this->parameters[] = $parameter;
                return $this->expr->eq($this->rootAlias . '.' . $comparison->getField(), $placeholder);

            case Comparison::NEQ:
                if ($this->walkValue($comparison->getValue()) === null) {
                    return $this->expr->isNotNull($this->rootAlias . '.' . $comparison->getField());
                }
                $this->parameters[] = $parameter;
                return $this->expr->neq($this->rootAlias . '.' . $comparison->getField(), $placeholder);

            default:
                $operator = self::convertComparisonOperator($comparison->getOperator());
                if ($operator) {
                    $this->parameters[] = $parameter;
                    return new Expr\Comparison(
                        $this->rootAlias . '.' . $comparison->getField(),
                        $operator,
                        $placeholder
                    );
                }

                throw new \RuntimeException("Unknown comparison operator: " . $comparison->getOperator());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }
}
