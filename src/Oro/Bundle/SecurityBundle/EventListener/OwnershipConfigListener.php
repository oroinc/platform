<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

/**
 * Handles entity configuration changes related to ownership metadata.
 *
 * This listener monitors entity configuration changes and manages the ownership
 * metadata cache. It clears the cache when ownership configuration changes and
 * warms up the cache for new or modified entities to ensure ownership decisions
 * are based on current configuration.
 */
class OwnershipConfigListener
{
    /** @var OwnershipMetadataProviderInterface */
    protected $provider;

    public function __construct(OwnershipMetadataProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function preFlush(PreFlushConfigEvent $event)
    {
        $config = $event->getConfig('extend');
        if (null === $config || $event->isFieldConfig()) {
            return;
        }

        $className = $config->getId()->getClassName();
        $this->provider->clearCache($className);

        $changeSet = $event->getConfigManager()->getConfigChangeSet($config);
        $isDeleted = isset($changeSet['state']) && $changeSet['state'][1] === ExtendScope::STATE_DELETE;
        if (!$isDeleted) {
            $this->provider->warmUpCache($className);
        }
    }
}
