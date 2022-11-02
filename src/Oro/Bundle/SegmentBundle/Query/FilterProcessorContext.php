<?php

namespace Oro\Bundle\SegmentBundle\Query;

/**
 * The context for {@see \Oro\Bundle\SegmentBundle\Query\FilterProcessor}.
 */
class FilterProcessorContext extends SegmentQueryConverterContext
{
    /**
     * {@inheritDoc}
     */
    protected function validateDefinition(array $definition): void
    {
        // skip validation
    }
}
