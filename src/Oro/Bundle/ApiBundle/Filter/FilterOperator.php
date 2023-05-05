<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Provides a list of all the supported filtering operators which are implemented "out of the box".
 */
final class FilterOperator
{
    /** "equal to" operator */
    public const EQ = 'eq';
    /** "not equal to" operator */
    public const NEQ = 'neq';
    /** "less than" operator */
    public const LT = 'lt';
    /** "less than or equal to" operator */
    public const LTE = 'lte';
    /** "greater than" operator */
    public const GT = 'gt';
    /** "greater than or equal to" operator */
    public const GTE = 'gte';
    /**
     * "exists" operator,
     * value is true = EXISTS (IS NOT NULL for fields and to-one associations
     * and check whether a collection is not empty for to-many associations),
     * value is false = NOT EXISTS (IS NULL for fields and to-one associations
     * and check whether a collection is empty for to-many associations),
     */
    public const EXISTS = 'exists';
    /**
     * "not equal to or IS NULL" operator for fields and to-one associations,
     * for to-many associations checks whether a collection does not contain any of specific values
     * or a collection is empty
     */
    public const NEQ_OR_NULL = 'neq_or_null';
    /**
     * "contains" (LIKE %value%) operator for string fields
     * and to-one associations with string identifier,
     * for to-many associations checks whether a collection contains all of specific values
     */
    public const CONTAINS = 'contains';
    /**
     * "not contains" (NOT LIKE %value%) operator for string fields
     * and to-one associations with string identifier,
     * for to-many associations checks whether a collection does not contain all of specific values
     */
    public const NOT_CONTAINS = 'not_contains';
    /** "starts with" (LIKE value%) operator */
    public const STARTS_WITH = 'starts_with';
    /** "not starts with" (NOT LIKE value%) operator */
    public const NOT_STARTS_WITH = 'not_starts_with';
    /** "ends with" (LIKE %value) operator */
    public const ENDS_WITH = 'ends_with';
    /** "not ends with" (NOT LIKE %value) operator */
    public const NOT_ENDS_WITH = 'not_ends_with';
    /**
     * "empty" operator,
     * value is true = EQUAL TO empty value OR IS NULL,
     * value is false = NOT EQUAL TO empty value AND NOT IS NULL
     */
    public const EMPTY_VALUE = 'empty';
}
