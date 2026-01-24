<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * Handles entity configuration changes and clears the entity alias cache when needed.
 *
 * This listener responds to pre-flush configuration events and monitors changes to the
 * 'extend' configuration scope. When an entity's state transitions to 'active', it clears
 * the entity alias resolver cache to ensure that the new alias configuration is properly
 * reflected throughout the application.
 */
class EntityAliasConfigListener
{
    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    public function preFlush(PreFlushConfigEvent $event)
    {
        $config = $event->getConfig('extend');
        if (null === $config || $event->isFieldConfig()) {
            return;
        }

        $configManager = $event->getConfigManager();
        $changeSet = $configManager->getConfigChangeSet($config);
        if (isset($changeSet['state'])
            && $changeSet['state'][1] === ExtendScope::STATE_ACTIVE
        ) {
            $this->entityAliasResolver->clearCache();
        }
    }
}
