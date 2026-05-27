<?php

declare(strict_types=1);

namespace Oro\Component\Duplicator\Filter;

/**
 * Implement on a duplicator filter that needs access to the source entity being
 * duplicated. The Duplicator will call setSourceEntity() with the entity passed
 * to Duplicator::duplicate() right before the filter is added to DeepCopy.
 */
interface SourceEntityAwareFilterInterface
{
    public function setSourceEntity(object $sourceEntity): void;
}
