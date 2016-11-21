<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * This interface can be implemented by a filter that has a named value.
 */
interface NamedValueFilterInterface
{
    /**
     * Gets the name of the filter value.
     *
     * @return string
     */
    public function getFilterValueName();
}
