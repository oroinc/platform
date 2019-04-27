<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Oro\Component\ChainProcessor\ParameterBag;

/**
 * The container for key/value pairs where keys are case-insensitive.
 */
class CaseInsensitiveParameterBag extends ParameterBag
{
    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return parent::has(strtolower($key));
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return parent::get(strtolower($key));
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        parent::set(strtolower($key), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        parent::remove(strtolower($key));
    }
}
