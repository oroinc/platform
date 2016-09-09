<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;

/**
 * Sets field_acl_supported to true for custom entities to be able to turn Field level ACL
 * Cleanup entity security metadata in case if field_acl_enabled parameter was changed
 */
class FieldAclConfigListener
{
    /** @var EntitySecurityMetadataProvider */
    protected $metadataProvider;

    /**
     * @param EntitySecurityMetadataProvider $metadataProvider
     */
    public function __construct(EntitySecurityMetadataProvider $metadataProvider)
    {
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * @param PreFlushConfigEvent $event
     */
    public function preFlush(PreFlushConfigEvent $event)
    {
        // supports only entities
        if ($event->isFieldConfig()) {
            return;
        }

        $securityConfig = $event->getConfig('security');
        if (null === $securityConfig) {
            return;
        }

        // check if was changed field_acl_enabled parameter and clear cache
        $changeSet = $event->getConfigManager()->getConfigChangeSet($securityConfig);
        if (isset($changeSet['field_acl_enabled'])) {
            $this->metadataProvider->clearCache();
        }

        // set field_acl_supported in true for custom entities
        $config = $event->getConfig('extend');
        // supports only custom entities
        if (null === $config || $config->get('owner') !== ExtendScope::OWNER_CUSTOM) {
            return;
        }
        $securityConfig->set('field_acl_supported', true);
    }
}
