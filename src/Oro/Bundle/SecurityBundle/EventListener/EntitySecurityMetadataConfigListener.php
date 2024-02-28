<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;

/**
 * Clears security metadata in case if security entity config is updated.
 */
class EntitySecurityMetadataConfigListener
{
    /** @var EntitySecurityMetadataProvider */
    protected $provider;

    protected $hasPreflushChanges = false;

    public function __construct(EntitySecurityMetadataProvider $provider)
    {
        $this->provider = $provider;
    }

    public function preFlush(PreFlushConfigEvent $event)
    {
        if ($event->isFieldConfig()) {
            return;
        }

        $className      = $event->getClassName();
        $configProvider = $event->getConfigManager()->getProvider('security');
        if ($configProvider->hasConfig($className)) {
            $this->hasPreflushChanges = true;
        }
    }

    public function postFlush(PostFlushConfigEvent $event)
    {
        if ($this->hasPreflushChanges) {
            $this->provider->clearCache();
        }
        $this->hasPreflushChanges = false;
    }
}
