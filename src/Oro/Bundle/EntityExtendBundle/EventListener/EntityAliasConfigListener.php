<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class EntityAliasConfigListener
{
    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * @param PreFlushConfigEvent $event
     */
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
