<?php

namespace Oro\Bundle\SearchBundle\Query\Criteria;

use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\ExpressionBuilder as BaseExpressionBuilder;

class ExpressionBuilder extends BaseExpressionBuilder
{
    /**
     * @param string $field
     * @param string $value
     *
     * @return Comparison
     */
    public function notContains($field, $value)
    {
        return new Comparison($field, Comparison::NOT_CONTAINS, new Value($value));
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return Comparison
     */
    public function startsWith($field, $value)
    {
        return new Comparison($field, Comparison::STARTS_WITH, new Value($value));
    }

    /**
     * @param string $field
     * @return Comparison
     */
    public function exists($field)
    {
        return new Comparison($field, Comparison::EXISTS, new Value(null));
    }

    /**
     * @param string $field
     * @return Comparison
     */
    public function notExists($field)
    {
        return new Comparison($field, Comparison::NOT_EXISTS, new Value(null));
    }

    /**
     * @param string $field
     * @param string $value
     * @return Comparison
     */
    public function like($field, $value)
    {
        return new Comparison($field, Comparison::LIKE, new Value($value));
    }

    /**
     * @param string $field
     * @param string $value
     * @return Comparison
     */
    public function notLike($field, $value)
    {
        return new Comparison($field, Comparison::NOT_LIKE, new Value($value));
    }
}
