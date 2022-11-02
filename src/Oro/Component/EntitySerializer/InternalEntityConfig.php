<?php

namespace Oro\Component\EntitySerializer;

/**
 * This class is used by EntitySerializer instead of EntityConfig
 * and allows caching intermediate data related to an entity.
 */
final class InternalEntityConfig extends EntityConfig
{
    private array $cache = [];

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), $this->cache);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->cache) || parent::has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $defaultValue = null): mixed
    {
        if (\array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        return parent::get($key, $defaultValue);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->cache[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key): void
    {
        unset($this->cache[$key]);
        parent::remove($key);
    }
}
