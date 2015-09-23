<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;

class ConfigListener
{
    /** @var Cache */
    protected $cache;

    /** @var  string */
    protected $cacheKey;

    /**
     * @param Cache  $cache
     * @param string $cacheKey
     */
    public function __construct(Cache $cache, $cacheKey)
    {
        $this->cache    = $cache;
        $this->cacheKey = $cacheKey;
    }

    /**
     * @param PreFlushConfigEvent $event
     */
    public function preFlush(PreFlushConfigEvent $event)
    {
        $config = $event->getConfig('email');
        if (null === $config || !$event->isFieldConfig()) {
            return;
        }

        $changeSet = $event->getConfigManager()->getConfigChangeSet($config);
        if (isset($changeSet['available_in_template'])) {
            $this->cache->delete($this->cacheKey);
        }
    }
}
