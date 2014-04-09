<?php

namespace Oro\Component\Config\Loader;

class CumulativeResourceLoaderCollection implements \Countable, \Iterator, \Serializable
{
    /**
     * @var CumulativeResourceLoader[]
     */
    protected $loaders;

    /**
     * @param CumulativeResourceLoader[] $loaders
     */
    public function __construct($loaders = [])
    {
        $this->loaders = $loaders;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->loaders);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        reset($this->loaders);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return current($this->loaders);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->loaders);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->loaders);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        $key = key($this->loaders);

        return ($key !== null && $key !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->loaders);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->loaders = unserialize($serialized);
    }
}
