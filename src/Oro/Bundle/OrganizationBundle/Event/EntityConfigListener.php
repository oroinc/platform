<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;

class EntityConfigListener
{
    /**
     * @param PreFlushConfigEvent $event
     */
    public function preFlush(PreFlushConfigEvent $event)
    {
        $config = $event->getConfig('ownership');
        if (null === $config || $event->isFieldConfig()) {
            return;
        }

        $haveChanges = false;
        $ownerType   = $config->get('owner_type');
        if ($ownerType === 'NONE') {
            $ownerType = null;
            $config->remove('owner_type');
            $haveChanges = true;
        }
        if ($ownerType && $this->isCustomEntity($config->getId()->getClassName(), $event->getConfigManager())) {
            if (!$config->has('owner_field_name')) {
                // update 'ownership' config for entity
                $config->set('owner_field_name', 'owner');
                $config->set('owner_column_name', 'owner_id');
                $haveChanges = true;
            }
            if (!$config->has('organization_field_name')
                && in_array($ownerType, [OwnershipType::OWNER_TYPE_USER, OwnershipType::OWNER_TYPE_BUSINESS_UNIT], true)
            ) {
                // update organization config for entity
                $config->set('organization_field_name', 'organization');
                $config->set('organization_column_name', 'organization_id');
                $haveChanges = true;
            }
        }

        if ($haveChanges) {
            $configManager = $event->getConfigManager();
            $configManager->persist($config);
            $configManager->calculateConfigChangeSet($config);
        }
    }

    /**
     * @param string        $className
     * @param ConfigManager $configManager
     *
     * @return bool
     */
    protected function isCustomEntity($className, ConfigManager $configManager)
    {
        $extendConfig = $configManager->getProvider('extend')->getConfig($className);

        return $extendConfig->is('owner', ExtendScope::OWNER_CUSTOM);
    }
}
