<?php

namespace Oro\Component\Config;

use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * CumulativeResource represents a resource which can be located in any bundle
 * and does not required any special registration in a bundle.
 */
class CumulativeResource implements ResourceInterface, \Serializable
{
    /**
     * @var mixed
     */
    private $resource;

    /**
     * @param mixed $resource The resource tied to this Resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($timestamp)
    {
        return CumulativeResourceManager::getInstance()->isFresh($this->resource, $timestamp);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string)$this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->resource = unserialize($serialized);
    }
}
