<?php

namespace Oro\Bundle\CacheBundle\Config;

use Symfony\Component\Config\Resource\ResourceInterface;

class CumulativeResource implements ResourceInterface, \Serializable
{
    /**
     * @var string
     */
    private $resource;

    /**
     * @param string $resource The name of resource
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
