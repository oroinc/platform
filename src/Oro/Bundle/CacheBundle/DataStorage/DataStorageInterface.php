<?php

namespace Oro\Bundle\CacheBundle\DataStorage;

/**
 * Interface for cache data storage of arbitrary data.
 */
interface DataStorageInterface
{
    /**
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * @param mixed $value
     */
    public function set(string $key, $value);

    /**
     * @return bool
     */
    public function has(string $key);

    public function remove(string $key);
}
