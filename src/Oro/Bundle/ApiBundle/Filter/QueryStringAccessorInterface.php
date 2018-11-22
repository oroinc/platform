<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Provides an interface for classes that can be used to get URL-encoded query string
 * representation of all requested filters.
 */
interface QueryStringAccessorInterface
{
    /**
     * Returns URL-encoded query string representation of all requested filters.
     *
     * @return string
     */
    public function getQueryString(): string;
}
