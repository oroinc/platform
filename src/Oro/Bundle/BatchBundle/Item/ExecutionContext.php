<?php

namespace Oro\Bundle\BatchBundle\Item;

/**
 * Object representing a job execution context.
 */
class ExecutionContext
{
    private bool $dirty = false;

    private array $context = [];

    public function isDirty(): bool
    {
        return $this->dirty;
    }

    public function clearDirtyFlag(): self
    {
        $this->dirty = false;

        return $this;
    }

    /**
     * Get the value associated with the key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->context[$key] ?? null;
    }

    /**
     * Put a key-value pair in the context
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return self
     */
    public function put(string $key, $value): self
    {
        $this->context[$key] = $value;

        $this->dirty = true;

        return $this;
    }

    /**
     * Remove a key-value pair from the context
     * by using the key
     */
    public function remove(string $key): self
    {
        if (isset($this->context[$key])) {
            unset($this->context[$key]);
        }

        return $this;
    }

    /**
     * Provides the list of keys available in the context
     *
     * @return array $keys
     */
    public function getKeys(): array
    {
        return array_keys($this->context);
    }
}
