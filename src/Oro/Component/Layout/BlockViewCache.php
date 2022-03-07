<?php

namespace Oro\Component\Layout;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The cache for layout block views.
 */
class BlockViewCache implements BlockViewCacheInterface
{
    protected CacheInterface $cache;
    protected Serializer $serializer;

    public function __construct(CacheInterface $cacheProvider, Serializer $serializer)
    {
        $this->cache = $cacheProvider;
        $this->serializer = $serializer;
    }

    public function save(ContextInterface $context, BlockView $rootView): void
    {
        $this->doCache($context->getHash(), $this->serializer->serialize($rootView, JsonEncoder::FORMAT));
    }

    public function fetch(ContextInterface $context): ?BlockView
    {
        $hash = $context->getHash();
        $value = $this->doCache($hash);

        return null !== $value
            ? $this->serializer->deserialize($value, BlockView::class, JsonEncoder::FORMAT, ['context_hash' => $hash])
            : null;
    }

    public function reset()
    {
        $this->cache->clear();
    }

    private function doCache(string $cacheKey, ?string $data = null): ?string
    {
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey($cacheKey);
        if ($data) {
            $this->cache->delete($cacheKey);
        }
        return $this->cache->get($cacheKey, function () use ($data) {
            return $data;
        });
    }
}
