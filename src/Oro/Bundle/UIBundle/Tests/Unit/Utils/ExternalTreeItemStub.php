<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Utils;

class ExternalTreeItemStub implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /** @var ExternalTreeItemStub[] */
    protected $children = [];

    /** @var string */
    protected $id;

    /** @var string */
    protected $title;

    /**
     * @param string                 $id
     * @param string                 $title
     * @param ExternalTreeItemStub[] $children
     */
    public function __construct($id, $title, $children = [])
    {
        $this->id = $id;
        $this->title = $title;
        $this->children = $children;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->children[$offset] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->children[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->children[$offset]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->children);
    }
}
