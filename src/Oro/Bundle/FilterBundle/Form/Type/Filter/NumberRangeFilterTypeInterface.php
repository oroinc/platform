<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

/**
 * Defines the contract for number range filter types.
 *
 * This interface extends {@see NumberFilterTypeInterface} to add support for range-based
 * filtering operators. It defines constants for `between` and `not between` operators,
 * allowing users to filter records based on whether numeric values fall within or
 * outside a specified range.
 */
interface NumberRangeFilterTypeInterface extends NumberFilterTypeInterface
{
    public const TYPE_BETWEEN          = 7;
    public const TYPE_NOT_BETWEEN      = 8;
}
