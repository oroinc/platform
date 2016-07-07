<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * Sets field_acl_supported to true for custom entities to be able to turn Field level ACL
 */
class FieldAclConfigListener
{
    /**
     * @param PreFlushConfigEvent $event
     */
    public function preFlush(PreFlushConfigEvent $event)
    {
        // supports only entities
        if ($event->isFieldConfig()) {
            return;
        }

        $config = $event->getConfig('extend');
        // supports only custom entities
        if (null === $config || $config->get('owner') !== ExtendScope::OWNER_CUSTOM) {
            return;
        }

        $securityConfig = $event->getConfig('security');
        if (null === $securityConfig) {
            return;
        }

        $securityConfig->set('field_acl_supported', true);
    }
}
