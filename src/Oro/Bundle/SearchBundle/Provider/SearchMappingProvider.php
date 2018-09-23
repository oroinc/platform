<?php

namespace Oro\Bundle\SearchBundle\Provider;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The search mapping provider.
 */
class SearchMappingProvider extends AbstractSearchMappingProvider
{
    const CACHE_KEY = 'oro_search.mapping_config';

    /** @var Cache */
    protected $cache;

    /** @var array */
    protected $mappingConfig;

    /** @var array|null */
    protected $processedConfig;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param Cache $cache
     */
    public function __construct(EventDispatcherInterface $dispatcher, Cache $cache = null)
    {
        $this->dispatcher = $dispatcher;
        $this->cache = $cache ?: new ArrayCache();
    }

    /**
     * @param array $mappingConfig
     */
    public function setMappingConfig($mappingConfig)
    {
        $this->mappingConfig = $mappingConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingConfig()
    {
        if (null !== $this->processedConfig) {
            return $this->processedConfig;
        }

        $cachedConfig = $this->cache->fetch(static::CACHE_KEY);
        if (false !== $cachedConfig) {
            $this->processedConfig = $cachedConfig;

            return $this->processedConfig;
        }

        $event = new SearchMappingCollectEvent($this->mappingConfig);
        $this->dispatcher->dispatch(SearchMappingCollectEvent::EVENT_NAME, $event);

        $this->processedConfig = $event->getMappingConfig();
        $this->cache->save(static::CACHE_KEY, $this->processedConfig);

        return $this->processedConfig;
    }

    public function clearCache()
    {
        $this->cache->delete(static::CACHE_KEY);
    }
}
