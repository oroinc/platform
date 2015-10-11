<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;

class EntitySecurityMetadataConfigListener
{
    /** @var EntitySecurityMetadataProvider */
    protected $provider;

    /**
     * @param EntitySecurityMetadataProvider $provider
     */
    public function __construct(EntitySecurityMetadataProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param PreFlushConfigEvent $event
     */
    public function preFlush(PreFlushConfigEvent $event)
    {
        if ($event->isFieldConfig()) {
            return;
        }

        $className      = $event->getClassName();
        $configProvider = $event->getConfigManager()->getProvider('security');
        if ($configProvider->hasConfig($className)) {
            $this->provider->clearCache($configProvider->getConfig($className)->get('type'));
        }
    }
}
