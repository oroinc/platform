<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Implements empty collection of the FilterValue objects.
 */
class NullFilterValueAccessor implements FilterValueAccessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($group = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, FilterValue $value = null)
    {
    }
}
