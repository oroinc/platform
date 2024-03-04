<?php

namespace Oro\Bundle\SegmentBundle\Placeholder;

/**
 * Placeholder filter that allow to show segment condition filter button on query builder.
 */
class SegmentConditionFilter implements SegmentConditionFilterInterface
{
    public function isSegmentFilterShouldBeAdded(): bool
    {
        return true;
    }
}
