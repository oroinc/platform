<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * This interface should be implemented by filters that have a special handling
 * and as result the common normalization should not be applied to theirs values.
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\NormalizeFilterValues::normalizeFilterValues
 */
interface SpecialHandlingFilterInterface
{
}
