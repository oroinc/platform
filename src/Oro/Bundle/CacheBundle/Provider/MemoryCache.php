<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Provides functionality to cache data into a memory.
 */
class MemoryCache
{
    private array $data = [];

    /**
     * Fetches an entry from the cache.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed The value of the item from the cache,
     *               or the given default value in case an entry does not exists in the cache
     */
    public function get(string $key, mixed $default = null)
    {
        if (!\array_key_exists($key, $this->data)) {
            return $default;
        }

        return $this->data[$key];
    }

    /**
     * Checks if an entry exists in the cache.
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->data);
    }

    /**
     * Puts data into the cache.
     *
     * If a cache entry with the given key already exists, its data will be replaced.
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Deletes a cache entry.
     */
    public function delete(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Deletes all cache entries.
     */
    public function deleteAll(): void
    {
        $this->data = [];
    }
}
