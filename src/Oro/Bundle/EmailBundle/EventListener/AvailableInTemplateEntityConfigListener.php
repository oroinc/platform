<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Component\Config\Cache\ClearableConfigCacheInterface;

/**
 * Clears email renderer configuration cache when related entity configuration is changed.
 */
class AvailableInTemplateEntityConfigListener
{
    private ClearableConfigCacheInterface $clearableConfigCache;

    public function __construct(ClearableConfigCacheInterface $clearableConfigCache)
    {
        $this->clearableConfigCache = $clearableConfigCache;
    }

    public function preFlush(PreFlushConfigEvent $event)
    {
        $config = $event->getConfig('email');
        if (null === $config || $event->isEntityConfig()) {
            return;
        }

        $changeSet = $event->getConfigManager()->getConfigChangeSet($config);
        $isNewField = !$event->getConfigManager()->getConfigModelId(
            $config->getId()->getClassName(),
            $config->getId()->getFieldName()
        );

        if ($isNewField || isset($changeSet['available_in_template'])) {
            $this->clearableConfigCache->clearCache();
        }
    }
}
