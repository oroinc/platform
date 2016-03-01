<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

interface NumberRangeFilterTypeInterface extends NumberFilterTypeInterface
{
    const TYPE_BETWEEN          = 7;
    const TYPE_NOT_BETWEEN      = 8;
}
