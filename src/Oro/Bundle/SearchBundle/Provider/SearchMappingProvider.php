<?php

namespace Oro\Bundle\SearchBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\SearchBundle\Configuration\MappingConfigurationProvider;
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
    private const CACHE_KEY = 'oro_search.mapping_config';

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var MappingConfigurationProvider */
    private $mappingConfigProvider;

    /** @var Cache */
    private $cache;

    /** @var array|null */
    private $configuration;

    /**
     * @param EventDispatcherInterface     $dispatcher
     * @param MappingConfigurationProvider $mappingConfigProvider
     * @param Cache                        $cache
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        MappingConfigurationProvider $mappingConfigProvider,
        Cache $cache
    ) {
        $this->dispatcher = $dispatcher;
        $this->mappingConfigProvider = $mappingConfigProvider;
        $this->cache = $cache;
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
        $this->cache->delete(self::CACHE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache(): void
    {
        $this->configuration = null;
        $this->cache->delete(self::CACHE_KEY);
        $this->getMappingConfig();
    }

    /**
     * @return array|null
     */
    private function fetchMappingConfigFromCache(): ?array
    {
        $config = null;
        $cachedData = $this->cache->fetch(self::CACHE_KEY);
        if (false !== $cachedData) {
            list($timestamp, $value) = $cachedData;
            if ($this->mappingConfigProvider->isCacheFresh($timestamp)) {
                $config = $value;
            }
        }

        return $config;
    }

    /**
     * @param array $config
     */
    private function saveMappingConfigToCache(array $config): void
    {
        $this->cache->save(self::CACHE_KEY, [$this->mappingConfigProvider->getCacheTimestamp(), $config]);
    }

    /**
     * @return array
     */
    private function loadMappingConfig(): array
    {
        $event = new SearchMappingCollectEvent($this->mappingConfigProvider->getConfiguration());
        $this->dispatcher->dispatch(SearchMappingCollectEvent::EVENT_NAME, $event);

        return $event->getMappingConfig();
    }
}
