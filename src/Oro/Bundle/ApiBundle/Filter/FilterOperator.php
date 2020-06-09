<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Provides a list of all the supported filtering operators which are implemented "out of the box".
 */
final class FilterOperator
{
    /** @var string "equal to" operator */
    public const EQ = 'eq';
    /** @var string "not equal to" operator */
    public const NEQ = 'neq';
    /** @var string "less than" operator */
    public const LT = 'lt';
    /** @var string "less than or equal to" operator */
    public const LTE = 'lte';
    /** @var string "greater than" operator */
    public const GT = 'gt';
    /** @var string "greater than or equal to" operator */
    public const GTE = 'gte';
    /**
     * @var string "exists" operator,
     * value is true = EXISTS (IS NOT NULL for fields and to-one associations
     * and check whether a collection is not empty for to-many associations),
     * value is false = NOT EXISTS (IS NULL for fields and to-one associations
     * and check whether a collection is empty for to-many associations),
     */
    public const EXISTS = 'exists';
    /**
     * @var string "not equal to or IS NULL" operator for fields and to-one associations,
     * for to-many associations checks whether a collection does not contain any of specific values
     * or a collection is empty
     */
    public const NEQ_OR_NULL = 'neq_or_null';
    /**
     * @var string "contains" (LIKE %value%) operator for string fields
     * and to-one associations with string identifier,
     * for to-many associations checks whether a collection contains all of specific values
     */
    public const CONTAINS = 'contains';
    /**
     * @var string "not contains" (NOT LIKE %value%) operator for string fields
     * and to-one associations with string identifier,
     * for to-many associations checks whether a collection does not contain all of specific values
     */
    public const NOT_CONTAINS = 'not_contains';
    /** @var string "starts with" (LIKE value%) operator */
    public const STARTS_WITH = 'starts_with';
    /** @var string "not starts with" (NOT LIKE value%) operator */
    public const NOT_STARTS_WITH = 'not_starts_with';
    /** @var string "ends with" (LIKE %value) operator */
    public const ENDS_WITH = 'ends_with';
    /** @var string "not ends with" (NOT LIKE %value) operator */
    public const NOT_ENDS_WITH = 'not_ends_with';
}
