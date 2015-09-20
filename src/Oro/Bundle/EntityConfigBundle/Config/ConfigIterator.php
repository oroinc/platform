<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

/**
 * Represents an iterator which can be used to get configs grouped by entity and field.
 */
class ConfigIterator implements \Iterator
{
    /** @var ConfigInterface[] */
    private $configs;

    /** @var \ArrayIterator|null */
    private $iterator;

    /**
     * @param ConfigInterface[] $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->iterator->current();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->iterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        if (null === $this->iterator) {
            $this->iterator = new \ArrayIterator($this->configs);
        }

        $this->iterator->rewind();
    }
}
