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
    public function getGroup($group)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAll()
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
