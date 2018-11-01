<?php

namespace Oro\Component\Layout;

use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * The cache for layout block views.
 */
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

    /**
     * @param CacheProvider $cacheProvider
     * @param Serializer    $serializer
     */
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
        $value = $this->cache->fetch($hash);

        return false !== $value
            ? $this->serializer->deserialize($value, BlockView::class, JsonEncoder::FORMAT)
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->cache->deleteAll();
    }
}
