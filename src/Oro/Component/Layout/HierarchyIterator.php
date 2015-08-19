<?php

namespace Oro\Component\Layout;

/**
 * Represents an iterator which can be used to get child elements of a hierarchy
 * The iteration is performed from parent to child
 */
class HierarchyIterator implements \Iterator
{
    /** @var mixed */
    protected $id;

    /** @var \RecursiveIteratorIterator */
    protected $iterator;

    /**
     * @param mixed $id
     * @param array $children
     */
    public function __construct($id, array $children)
    {
        $this->id = $id;

        $this->iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($children),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /**
     * Return the parent element
     *
     * @return mixed
     */
    public function getParent()
    {
        $depth = $this->iterator->getDepth();

        return $depth === 0
            ? $this->id
            : $this->iterator->getSubIterator($depth - 1)->key();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->iterator->key();
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
        $this->iterator->rewind();
    }
}
