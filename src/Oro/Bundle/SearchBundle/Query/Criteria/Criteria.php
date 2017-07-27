<?php

namespace Oro\Bundle\SearchBundle\Query\Criteria;

use Doctrine\Common\Collections\Criteria as BaseCriteria;
use Oro\Bundle\SearchBundle\Query\Query;

class Criteria extends BaseCriteria
{
    /** @var ExpressionBuilder */
    private static $expressionBuilder;

    /**
     * {@inheritdoc}
     */
    public static function expr()
    {
        if (self::$expressionBuilder === null) {
            self::$expressionBuilder = new ExpressionBuilder();
        }

        return self::$expressionBuilder;
    }

    /**
     * @param string $type
     * @param string $name
     *
     * @return string
     */
    public static function implodeFieldTypeName($type, $name)
    {
        return sprintf('%s.%s', $type, $name);
    }

    /**
     * @param string $field
     *
     * @return array
     *  [0] - field type
     *  [1] - field name
     */
    public static function explodeFieldTypeName($field)
    {
        $fieldType = Query::TYPE_TEXT;
        if (strpos($field, '.') !== false) {
            list($fieldType, $field) = explode('.', $field);
        }

        return [$fieldType, $field];
    }

    /**
     * Convert Comparison operator to Query operator
     *
     * @param string $operator
     *
     * @return string
     */
    public static function getSearchOperatorByComparisonOperator($operator)
    {
        switch ($operator) {
            case Comparison::LIKE:
                return Query::OPERATOR_LIKE;
            case Comparison::NOT_LIKE:
                return Query::OPERATOR_NOT_LIKE;
            case Comparison::CONTAINS:
                return Query::OPERATOR_CONTAINS;
            case Comparison::NOT_CONTAINS:
                return Query::OPERATOR_NOT_CONTAINS;
            case Comparison::NEQ:
                return Query::OPERATOR_NOT_EQUALS;
            case Comparison::NIN:
                return Query::OPERATOR_NOT_IN;
            case Comparison::STARTS_WITH:
                return Query::OPERATOR_STARTS_WITH;
            case Comparison::EXISTS:
                return Query::OPERATOR_EXISTS;
            case Comparison::NOT_EXISTS:
                return Query::OPERATOR_NOT_EXISTS;
        }

        return strtolower($operator);
    }
}
