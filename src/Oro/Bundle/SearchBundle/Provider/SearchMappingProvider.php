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
    private EventDispatcherInterface $dispatcher;
    private MappingConfigurationProviderAbstract $mappingConfigProvider;
    private CacheItemPoolInterface $cache;
    private ?array $configuration = null;
    private string $cacheKey;
    private string $eventName;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        MappingConfigurationProviderAbstract $mappingConfigProvider,
        CacheItemPoolInterface $cache,
        string $cacheKeyPrefix,
        string $searchEngineName,
        string $eventName
    ) {
        $this->dispatcher = $dispatcher;
        $this->mappingConfigProvider = $mappingConfigProvider;
        $this->cache = $cache;
        $this->cacheKey = $cacheKeyPrefix . ':' . $searchEngineName;
        $this->eventName = $eventName;
    }

    public function getMappingConfig(): array
    {
        if (null === $this->configuration) {
            $cacheItem = $this->cache->getItem($this->getCacheKey());
            $config = $this->fetchMappingConfigFromCache($cacheItem);
            if (null === $config) {
                $config = $this->loadMappingConfig();
                $this->saveMappingConfigToCache($cacheItem, $config);
            }
            $this->configuration = $config;
        }

        return $this->configuration;
    }

    public function clearCache(): void
    {
        $this->configuration = null;
        $this->cache->deleteItem($this->getCacheKey());
    }

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
