<?php

namespace Oro\Bundle\SegmentBundle\Placeholder;

/**
 * Placeholder filter that allow to show segment condition filter button on query builder.
 */
interface SegmentConditionFilterInterface
{
    public function isSegmentFilterShouldBeAdded(): bool;
}
