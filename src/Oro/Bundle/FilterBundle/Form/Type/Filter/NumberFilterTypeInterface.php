<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

/**
 * Interface of numeric filters. Stores filter metadata such as available filter types and number types.
 */
interface NumberFilterTypeInterface
{
    public const TYPE_GREATER_EQUAL = 1;
    public const TYPE_GREATER_THAN  = 2;
    public const TYPE_EQUAL         = 3;
    public const TYPE_NOT_EQUAL     = 4;
    public const TYPE_LESS_EQUAL    = 5;
    public const TYPE_LESS_THAN     = 6;

    public const TYPE_IN            = 9; // 7 and 8 are already taken by number range filter
    public const TYPE_NOT_IN        = 10;

    public const DATA_SMALLINT = 'data_smallint';
    public const DATA_INTEGER = 'data_integer';
    public const DATA_BIGINT = 'data_bigint';
    public const DATA_DECIMAL = 'data_decimal';
    public const PERCENT      = 'percent';

    public const ARRAY_TYPES = [self::TYPE_IN, self::TYPE_NOT_IN];
}
