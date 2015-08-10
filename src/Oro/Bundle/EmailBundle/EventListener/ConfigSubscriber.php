<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Common\Cache\Cache;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;

class ConfigSubscriber implements EventSubscriberInterface
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
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_PERSIST_CONFIG => 'persistConfig'
        ];
    }

    /**
     * @param PersistConfigEvent $event
     */
    public function persistConfig(PersistConfigEvent $event)
    {
        $config = $event->getConfig();

        if ($config->getId()->getScope() !== 'email') {
            return;
        }

        $change = $event->getConfigManager()->getConfigChangeSet($config);
        if (isset($change['available_in_template'])) {
            $this->cache->delete($this->cacheKey);
        }
    }
}
