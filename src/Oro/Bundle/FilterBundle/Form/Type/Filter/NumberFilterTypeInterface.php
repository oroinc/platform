<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

interface NumberFilterTypeInterface
{
    const TYPE_GREATER_EQUAL = 1;
    const TYPE_GREATER_THAN  = 2;
    const TYPE_EQUAL         = 3;
    const TYPE_NOT_EQUAL     = 4;
    const TYPE_LESS_EQUAL    = 5;
    const TYPE_LESS_THAN     = 6;

    const DATA_INTEGER = 'data_integer';
    const DATA_DECIMAL = 'data_decimal';
    const PERCENT      = 'percent';
}
