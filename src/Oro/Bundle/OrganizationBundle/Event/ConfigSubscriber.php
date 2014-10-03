<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\Events;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_PERSIST_CONFIG => ['prePersistEntityConfig', 100]
        ];
    }

    /**
     * @param PersistConfigEvent $event
     */
    public function prePersistEntityConfig(PersistConfigEvent $event)
    {
        $className               = $event->getConfig()->getId()->getClassName();
        $configManager           = $event->getConfigManager();
        $ownershipConfigProvider = $configManager->getProvider('ownership');
        if ($ownershipConfigProvider->hasConfig($className)) {
            $ownershipConfig = $ownershipConfigProvider->getConfig($className);
            $haveChanges     = false;
            $ownerType       = $ownershipConfig->get('owner_type');
            if ($ownerType === 'NONE') {
                $ownerType = null;
                $ownershipConfig->remove('owner_type');
                $haveChanges = true;
            }
            if ($ownerType && $this->isCustomEntity($className, $configManager)) {
                if (!$ownershipConfig->has('owner_field_name')) {
                    // update 'ownership' config for entity
                    $ownershipConfig->set('owner_field_name', 'owner');
                    $ownershipConfig->set('owner_column_name', 'owner_id');
                    $haveChanges = true;
                }
                if (!$ownershipConfig->has('organization_field_name')
                    && in_array($ownerType, [OwnershipType::OWNER_TYPE_USER, OwnershipType::OWNER_TYPE_BUSINESS_UNIT])
                ) {
                    // update organization config for entity
                    $ownershipConfig->set('organization_field_name', 'organization');
                    $ownershipConfig->set('organization_column_name', 'organization_id');
                    $haveChanges = true;
                }
            }

            if ($haveChanges) {
                $configManager->persist($ownershipConfig);
                $configManager->calculateConfigChangeSet($ownershipConfig);
            }
        }
    }

    /**
     * @param string        $className
     * @param ConfigManager $configManager
     * @return bool
     */
    protected function isCustomEntity($className, ConfigManager $configManager)
    {
        $extendConfigProvider = $configManager->getProvider('extend');
        if ($extendConfigProvider->hasConfig($className)) {
            $extendConfig = $extendConfigProvider->getConfig($className);
            if ($extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
                return true;
            }
        }

        return false;
    }
}
