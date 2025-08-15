<?php

namespace Oro\Bundle\SearchBundle\Provider;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\SearchBundle\Configuration\MappingConfigurationProviderAbstract;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Component\Config\Cache\ClearableConfigCacheInterface;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The search mapping provider.
 */
class SearchMappingProvider extends AbstractSearchMappingProvider implements
    WarmableConfigCacheInterface,
    ClearableConfigCacheInterface
{
    private ?array $configuration = null;
    private string $cacheKey;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly MappingConfigurationProviderAbstract $mappingConfigProvider,
        private readonly CacheItemPoolInterface $cache,
        private readonly SearchMappingCacheNormalizer $cacheNormalizer,
        private readonly string $eventName,
        string $cacheKeyPrefix,
        string $searchEngineName
    ) {
        $this->cacheKey = $cacheKeyPrefix . ':' . $searchEngineName;
    }

    #[\Override]
    public function getMappingConfig(): array
    {
        if (null === $this->configuration) {
            $cacheItem = $this->cache->getItem($this->getCacheKey());
            $config = $this->fetchMappingConfigFromCache($cacheItem);
            if (null === $config) {
                $config = $this->loadMappingConfig();
                $this->saveMappingConfigToCache($cacheItem, $this->cacheNormalizer->normalize($config));
            } else {
                $config = $this->cacheNormalizer->denormalize($config);
            }
            $this->configuration = $config;
        }

        return $this->configuration;
    }

    #[\Override]
    public function clearCache(): void
    {
        $this->configuration = null;
        $this->cache->deleteItem($this->getCacheKey());
    }

    #[\Override]
    public function warmUpCache(): void
    {
        $this->configuration = null;
        $this->cache->deleteItem($this->getCacheKey());
        $this->getMappingConfig();
    }

    private function fetchMappingConfigFromCache(CacheItemInterface $cacheItem): ?array
    {
        $config = null;
        if ($cacheItem->isHit()) {
            [$timestamp, $value] = $cacheItem->get();
            if ($this->mappingConfigProvider->isCacheFresh($timestamp)) {
                $config = $value;
            }
        }

        return $config;
    }

    private function saveMappingConfigToCache(CacheItemInterface $cacheItem, array $config): void
    {
        $cacheItem->set([$this->mappingConfigProvider->getCacheTimestamp(), $config]);
        $this->cache->save($cacheItem);
    }

    private function loadMappingConfig(): array
    {
        $event = new SearchMappingCollectEvent($this->mappingConfigProvider->getConfiguration());
        $this->dispatcher->dispatch($event, $this->eventName);

        return $event->getMappingConfig();
    }

    private function getCacheKey(): string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey($this->cacheKey);
    }
}
