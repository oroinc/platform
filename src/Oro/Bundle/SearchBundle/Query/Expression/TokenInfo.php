<?php

namespace Oro\Bundle\SearchBundle\Query\Expression;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Provides a set of methods to get information about search query tokens.
 */
class TokenInfo
{
    private const KEYWORDS = [
        Query::KEYWORD_SELECT,
        Query::KEYWORD_FROM,
        Query::KEYWORD_WHERE,
        Query::KEYWORD_AGGREGATE,
        Query::KEYWORD_AND,
        Query::KEYWORD_OR,
        Query::KEYWORD_OFFSET,
        Query::KEYWORD_MAX_RESULTS,
        Query::KEYWORD_ORDER_BY,
        Query::KEYWORD_AS
    ];

    private const LOGICAL_OPERATORS = [
        Query::KEYWORD_AND,
        Query::KEYWORD_OR
    ];

    private const TYPES = [
        Query::TYPE_TEXT,
        Query::TYPE_DATETIME,
        Query::TYPE_DECIMAL,
        Query::TYPE_INTEGER
    ];

    private const TYPE_OPERATORS = [
        Query::TYPE_TEXT     => [
            Query::OPERATOR_CONTAINS,
            Query::OPERATOR_NOT_CONTAINS,
            Query::OPERATOR_EQUALS,
            Query::OPERATOR_NOT_EQUALS,
            Query::OPERATOR_IN,
            Query::OPERATOR_NOT_IN,
            Query::OPERATOR_STARTS_WITH,
            Query::OPERATOR_EXISTS,
            Query::OPERATOR_NOT_EXISTS,
            Query::OPERATOR_LIKE,
            Query::OPERATOR_NOT_LIKE
        ],
        Query::TYPE_INTEGER  => [
            Query::OPERATOR_GREATER_THAN,
            Query::OPERATOR_GREATER_THAN_EQUALS,
            Query::OPERATOR_LESS_THAN,
            Query::OPERATOR_LESS_THAN_EQUALS,
            Query::OPERATOR_EQUALS,
            Query::OPERATOR_NOT_EQUALS,
            Query::OPERATOR_IN,
            Query::OPERATOR_NOT_IN,
            Query::OPERATOR_EXISTS,
            Query::OPERATOR_NOT_EXISTS
        ],
        Query::TYPE_DECIMAL  => [
            Query::OPERATOR_GREATER_THAN,
            Query::OPERATOR_GREATER_THAN_EQUALS,
            Query::OPERATOR_LESS_THAN,
            Query::OPERATOR_LESS_THAN_EQUALS,
            Query::OPERATOR_EQUALS,
            Query::OPERATOR_NOT_EQUALS,
            Query::OPERATOR_IN,
            Query::OPERATOR_NOT_IN,
            Query::OPERATOR_EXISTS,
            Query::OPERATOR_NOT_EXISTS
        ],
        Query::TYPE_DATETIME => [
            Query::OPERATOR_GREATER_THAN,
            Query::OPERATOR_GREATER_THAN_EQUALS,
            Query::OPERATOR_LESS_THAN,
            Query::OPERATOR_LESS_THAN_EQUALS,
            Query::OPERATOR_EQUALS,
            Query::OPERATOR_NOT_EQUALS,
            Query::OPERATOR_IN,
            Query::OPERATOR_NOT_IN,
            Query::OPERATOR_EXISTS,
            Query::OPERATOR_NOT_EXISTS
        ]
    ];

    private const AGGREGATING_FUNCTIONS = [
        Query::AGGREGATE_FUNCTION_COUNT,
        Query::AGGREGATE_FUNCTION_SUM,
        Query::AGGREGATE_FUNCTION_MAX,
        Query::AGGREGATE_FUNCTION_MIN,
        Query::AGGREGATE_FUNCTION_AVG
    ];

    private const TYPE_AGGREGATING_FUNCTIONS = [
        Query::TYPE_TEXT     => [
            Query::AGGREGATE_FUNCTION_COUNT
        ],
        Query::TYPE_INTEGER  => [
            Query::AGGREGATE_FUNCTION_COUNT,
            Query::AGGREGATE_FUNCTION_SUM,
            Query::AGGREGATE_FUNCTION_MAX,
            Query::AGGREGATE_FUNCTION_MIN,
            Query::AGGREGATE_FUNCTION_AVG
        ],
        Query::TYPE_DECIMAL  => [
            Query::AGGREGATE_FUNCTION_COUNT,
            Query::AGGREGATE_FUNCTION_SUM,
            Query::AGGREGATE_FUNCTION_MAX,
            Query::AGGREGATE_FUNCTION_MIN,
            Query::AGGREGATE_FUNCTION_AVG
        ],
        Query::TYPE_DATETIME => [
            Query::AGGREGATE_FUNCTION_COUNT,
            Query::AGGREGATE_FUNCTION_MAX,
            Query::AGGREGATE_FUNCTION_MIN
        ]
    ];

    private const ORDER_DIRECTIONS = [
        Query::ORDER_ASC,
        Query::ORDER_DESC
    ];

    /**
     * Gets all keywords.
     *
     * @return string[]
     */
    public static function getKeywords(): array
    {
        return self::KEYWORDS;
    }

    /**
     * Gets all keywords that represent logical operators, such as logical AND and logical OR.
     *
     * @return string[]
     */
    public static function getLogicalOperators(): array
    {
        return self::LOGICAL_OPERATORS;
    }

    /**
     * Gets all data-types.
     *
     * @return string[]
     */
    public static function getTypes(): array
    {
        return self::TYPES;
    }

    /**
     * Gets all operators for the given data-type.
     *
     * @param string $type
     *
     * @return string[]
     */
    public static function getOperatorsForType(string $type): array
    {
        return self::TYPE_OPERATORS[$type];
    }

    /**
     * Gets all aggregating functions.
     *
     * @return string[]
     */
    public static function getAggregatingFunctions(): array
    {
        return self::AGGREGATING_FUNCTIONS;
    }

    /**
     * Gets all aggregating functions for the given data-type.
     *
     * @param string $type
     *
     * @return string[]
     */
    public static function getAggregatingFunctionsForType(string $type): array
    {
        return self::TYPE_AGGREGATING_FUNCTIONS[$type];
    }

    /**
     * Gets all order directions.
     *
     * @return string[]
     */
    public static function getOrderDirections(): array
    {
        return self::ORDER_DIRECTIONS;
    }
}
