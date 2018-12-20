<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * This interface should be implemented by filters that can handle a collection valued association.
 */
interface CollectionAwareFilterInterface
{
    /**
     * Sets a flag indicates whether the filter represents a collection valued association.
     *
     * @param bool $collection
     */
    public function setCollection($collection);
}
