<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

/**
 * Interface of numeric filters. Stores filter metadata such as available filter types and number types.
 */
interface NumberFilterTypeInterface
{
    const TYPE_GREATER_EQUAL = 1;
    const TYPE_GREATER_THAN  = 2;
    const TYPE_EQUAL         = 3;
    const TYPE_NOT_EQUAL     = 4;
    const TYPE_LESS_EQUAL    = 5;
    const TYPE_LESS_THAN     = 6;

    const TYPE_IN            = 9; // 7 and 8 are already taken by number range filter
    const TYPE_NOT_IN        = 10;

    const DATA_SMALLINT = 'data_smallint';
    const DATA_INTEGER = 'data_integer';
    const DATA_BIGINT = 'data_bigint';
    const DATA_DECIMAL = 'data_decimal';
    const PERCENT      = 'percent';

    const ARRAY_TYPES = [self::TYPE_IN, self::TYPE_NOT_IN];
}
