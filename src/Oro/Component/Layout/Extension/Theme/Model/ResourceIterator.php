<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

class ResourceIterator implements \Iterator
{
    /** @var int */
    protected $currentKey;

    /** @var \RecursiveIteratorIterator */
    protected $iterator;

    /**
     * @param array $resources
     */
    public function __construct(array $resources)
    {
        $this->iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($resources),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
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
        return $this->currentKey;
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
        $this->currentKey++;
        $this->iterator->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->currentKey = 0;
        $this->iterator->rewind();
    }
}
