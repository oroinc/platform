<?php

namespace Oro\Bundle\CacheBundle\Mapping\Validator;

use Doctrine\Common\Cache\Cache;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Cache\CacheInterface;

class DoctrineCache implements CacheInterface
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @param Cache $cache caching interface to use
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }


    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function has($class)
    {
        return $this->cache->contains($class);
    }

    /**
     * {@inheritdoc}
     */
    public function read($class)
    {
        return $this->cache->fetch($class);
    }

    /**
     * {@inheritdoc}
     */
    public function write(ClassMetadata $metadata)
    {
        $this->cache->save($metadata->getClassName(), $metadata);
    }
}
