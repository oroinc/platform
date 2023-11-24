<?php

namespace Oro\Component\Layout;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The cache for layout block views.
 */
class BlockViewCache implements BlockViewCacheInterface
{
    private CacheInterface $cache;
    private SerializerInterface $serializer;

    public function __construct(CacheInterface $cacheProvider, SerializerInterface $serializer)
    {
        $this->cache = $cacheProvider;
        $this->serializer = $serializer;
    }

    public function save(ContextInterface $context, BlockView $rootView): void
    {
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey($context->getHash());
        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, function () use ($rootView) {
            return $this->serializer->serialize($rootView, JsonEncoder::FORMAT);
        });
    }

    public function fetch(ContextInterface $context): ?BlockView
    {
        $hash = $context->getHash();
        $value = $this->cache->get(UniversalCacheKeyGenerator::normalizeCacheKey($hash), function () {
            return null;
        });
        if (null !== $value) {
            $value = $this->serializer->deserialize(
                $value,
                BlockView::class,
                JsonEncoder::FORMAT,
                ['context_hash' => $hash]
            );
        }

        return $value;
    }

    public function reset(): void
    {
        $this->cache->clear();
    }
}
