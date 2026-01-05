<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

interface NumberRangeFilterTypeInterface extends NumberFilterTypeInterface
{
    public const TYPE_BETWEEN          = 7;
    public const TYPE_NOT_BETWEEN      = 8;
}
