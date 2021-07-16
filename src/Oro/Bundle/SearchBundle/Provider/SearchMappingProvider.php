<?php

namespace Oro\Bundle\SearchBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\SearchBundle\Configuration\MappingConfigurationProviderAbstract;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Component\Config\Cache\ClearableConfigCacheInterface;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The search mapping provider.
 */
class SearchMappingProvider extends AbstractSearchMappingProvider implements
    WarmableConfigCacheInterface,
    ClearableConfigCacheInterface
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var MappingConfigurationProviderAbstract */
    private $mappingConfigProvider;

    /** @var Cache */
    private $cache;

    /** @var array|null */
    private $configuration;

    /** @var string */
    private $cacheKey;

    /** @var string */
    private $eventName;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        MappingConfigurationProviderAbstract $mappingConfigProvider,
        Cache $cache,
        string $cacheKey,
        string $eventName
    ) {
        $this->dispatcher = $dispatcher;
        $this->mappingConfigProvider = $mappingConfigProvider;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
        $this->eventName = $eventName;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingConfig()
    {
        if (null === $this->configuration) {
            $config = $this->fetchMappingConfigFromCache();
            if (null === $config) {
                $config = $this->loadMappingConfig();
                $this->saveMappingConfigToCache($config);
            }
            $this->configuration = $config;
        }

        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        $this->configuration = null;
        $this->cache->delete($this->cacheKey);
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache(): void
    {
        $this->configuration = null;
        $this->cache->delete($this->cacheKey);
        $this->getMappingConfig();
    }

    private function fetchMappingConfigFromCache(): ?array
    {
        $config = null;
        $cachedData = $this->cache->fetch($this->cacheKey);
        if (false !== $cachedData) {
            [$timestamp, $value] = $cachedData;
            if ($this->mappingConfigProvider->isCacheFresh($timestamp)) {
                $config = $value;
            }
        }

        return $config;
    }

    private function saveMappingConfigToCache(array $config): void
    {
        $this->cache->save($this->cacheKey, [$this->mappingConfigProvider->getCacheTimestamp(), $config]);
    }

    private function loadMappingConfig(): array
    {
        $event = new SearchMappingCollectEvent($this->mappingConfigProvider->getConfiguration());
        $this->dispatcher->dispatch($event, $this->eventName);

        return $event->getMappingConfig();
    }
}
