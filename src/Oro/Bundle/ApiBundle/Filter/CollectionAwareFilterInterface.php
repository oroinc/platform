<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * This interface should be implemented by filters that can handle a collection valued association.
 */
interface CollectionAwareFilterInterface
{
    /**
     * Sets a flag indicates whether the filter is applied to a collection valued association.
     */
    public function setCollection(bool $collection): void;
}
