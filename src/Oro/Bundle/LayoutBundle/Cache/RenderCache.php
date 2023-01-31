<?php

namespace Oro\Bundle\LayoutBundle\Cache;

use LogicException;
use Oro\Bundle\LayoutBundle\Cache\Extension\RenderCacheExtensionInterface;
use Oro\Bundle\LayoutBundle\Cache\Metadata\CacheMetadataProviderInterface;
use Oro\Bundle\LayoutBundle\Cache\Metadata\LayoutCacheMetadata;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Cache for the rendered layout blocks HTML.
 */
class RenderCache
{
    /**
     * @var TagAwareAdapterInterface
     */
    private $cache;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var iterable|RenderCacheExtensionInterface[]
     */
    private $extensions;

    /**
     * @var string[]
     */
    private $alwaysVaryBy = [];

    /**
     * @var CacheMetadataProviderInterface
     */
    private $metadataProvider;

    /**
     * @var CacheItemInterface[]
     */
    private $fetchedItems = [];

    /**
     * @param TagAwareAdapterInterface $cache
     * @param CacheMetadataProviderInterface $metadataProvider
     * @param RequestStack $requestStack
     * @param RenderCacheExtensionInterface[]|iterable $extensions
     */
    public function __construct(
        TagAwareAdapterInterface $cache,
        CacheMetadataProviderInterface $metadataProvider,
        RequestStack $requestStack,
        iterable $extensions
    ) {
        $this->cache = $cache;
        $this->requestStack = $requestStack;
        $this->extensions = $extensions;
        $this->metadataProvider = $metadataProvider;
    }

    public function isEnabled(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request && $request->isMethodCacheable();
    }

    public function isCached(BlockView $blockView, ContextInterface $context): bool
    {
        $metadata = $this->getMetadata($blockView, $context);

        if (!$metadata) {
            return false;
        }

        if (!$this->isEnabled()) {
            return false;
        }

        $item = $this->getItem($blockView, $context);
        // prevents isCached and getItem from returning inconsistent result, when isCached returns true but on getItem
        // call it's already expired
        $this->fetchedItems[$item->getKey()] = $item;

        return $item->isHit();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getItem(BlockView $blockView, ContextInterface $context): CacheItemInterface
    {
        $metadata = $this->getMetadata($blockView, $context);
        if (!$metadata) {
            throw new LogicException(
                sprintf('Block "%s" is not cacheable, please provide "cache" option.', $blockView->getId())
            );
        }
        $cacheKey = $this->createCacheKey($blockView->getId(), $metadata);

        if (isset($this->fetchedItems[$cacheKey])) {
            $cacheItem = $this->fetchedItems[$cacheKey];
            // clear the local cache, as the consistency is needed only between isCached and getItem calls,
            // multiple calls of getItem must return fresh results
            unset($this->fetchedItems[$cacheKey]);

            return $cacheItem;
        }

        return $this->cache->getItem($cacheKey);
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->cache->save($item);
    }

    private function createCacheKey(string $blockId, LayoutCacheMetadata $metadata): string
    {
        $varyBy = array_merge($this->getAlwaysVaryBy(), $metadata->getVaryBy());

        $keyParts = [$blockId];

        foreach ($varyBy as $key => $value) {
            if (\is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif ($value === null) {
                $value = 'null';
            }
            $keyParts[] = $key . '='. $value;
        }

        return implode('|', $keyParts);
    }

    /**
     * {@inheritDoc}
     */
    private function getAlwaysVaryBy(): array
    {
        if (!$this->alwaysVaryBy) {
            $this->alwaysVaryBy = [];
            foreach ($this->extensions as $extension) {
                $this->alwaysVaryBy[] = $extension->alwaysVaryBy();
            }

            $this->alwaysVaryBy = array_merge(...$this->alwaysVaryBy);
        }

        return $this->alwaysVaryBy;
    }

    public function getMetadata(BlockView $blockView, ContextInterface $context): ?LayoutCacheMetadata
    {
        return $this->metadataProvider->getCacheMetadata($blockView, $context);
    }
}
