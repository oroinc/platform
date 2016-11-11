<?php

namespace Oro\Component\Layout;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

class BlockViewCache implements BlockViewCacheInterface
{
    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(CacheProvider $cacheProvider, Serializer $serializer)
    {
        $this->cache = $cacheProvider;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function save(ContextInterface $context, BlockView $rootView)
    {
        $hash = $context->getHash();

        $serialized = $this->serializer->serialize($rootView, JsonEncoder::FORMAT);

        $this->cache->save($hash, $serialized);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(ContextInterface $context)
    {
        $hash = $context->getHash();
        $cached = $this->cache->fetch($hash);

        if ($cached) {
            return $this->serializer->deserialize($cached, BlockView::class, JsonEncoder::FORMAT);
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->cache->deleteAll();
    }
}
