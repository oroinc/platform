<?php

namespace Oro\Component\EntitySerializer;

class InternalEntityConfig extends EntityConfig
{
    /** @var array */
    protected $cache = [];

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return array_key_exists($key, $this->cache) || parent::has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return array_key_exists($key, $this->cache)
            ? $this->cache[$key]
            : parent::get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->cache[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->cache[$key]);
        parent::remove($key);
    }
}
