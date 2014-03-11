<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;

use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\Events;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

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
        $ownershipConfigProvider = $event->getConfigManager()->getProvider('ownership');
        if ($ownershipConfigProvider->hasConfig($className)) {
            $ownershipConfig = $ownershipConfigProvider->getConfig($className);
            $ownerType       = $ownershipConfig->get('owner_type');
            if ($ownerType === 'NONE') {
                $ownerType = null;
                $ownershipConfig->remove('owner_type');
                $event->getConfigManager()->persist($ownershipConfig);
                $event->getConfigManager()->calculateConfigChangeSet($ownershipConfig);
            }
            if ($ownerType && !$ownershipConfig->has('owner_field_name')) {
                $ownerTargetEntity = $this->getOwnerTargetEntity($ownershipConfig->get('owner_type'));
                $ownerFieldName    = 'owner';

                $extendConfigProvider = $event->getConfigManager()->getProvider('extend');
                if (!$extendConfigProvider->hasConfig($className, $ownerFieldName)) {
                    // update 'ownership' config for entity
                    $ownershipConfig->set('owner_field_name', $ownerFieldName);
                    $ownershipConfig->set('owner_column_name', $ownerFieldName . '_id');
                    $event->getConfigManager()->persist($ownershipConfig);

                    // create 'owner' field
                    $ownerFieldType = 'manyToOne';
                    $event->getConfigManager()->createConfigFieldModel(
                        $className,
                        $ownerFieldName,
                        $ownerFieldType,
                        ConfigModelManager::MODE_READONLY
                    );
                    $this->updateFieldConfig(
                        $event->getConfigManager(),
                        'extend',
                        $className,
                        $ownerFieldName,
                        [
                            'extend'        => true,
                            'state'         => ExtendScope::STATE_NEW,
                            'owner'         => ExtendScope::OWNER_CUSTOM,
                            'target_entity' => $ownerTargetEntity,
                            'target_field'  => 'id',
                            'relation_key'  =>
                                ExtendHelper::buildRelationKey(
                                    $className,
                                    $ownerFieldName,
                                    $ownerFieldType,
                                    $ownerTargetEntity
                                )
                        ]
                    );
                    $this->updateFieldConfig(
                        $event->getConfigManager(),
                        'entity',
                        $className,
                        $ownerFieldName,
                        [
                            'label'       => 'Owner',
                            'description' => 'Owner Field Description'
                        ]
                    );
                    $this->updateFieldConfig(
                        $event->getConfigManager(),
                        'view',
                        $className,
                        $ownerFieldName,
                        [
                            'is_displayable' => false
                        ]
                    );
                    $this->updateFieldConfig(
                        $event->getConfigManager(),
                        'form',
                        $className,
                        $ownerFieldName,
                        [
                            'is_enabled' => false
                        ]
                    );
                    $this->updateFieldConfig(
                        $event->getConfigManager(),
                        'dataaudit',
                        $className,
                        $ownerFieldName,
                        [
                            'auditable' => true
                        ]
                    );
                }
            }
        }
    }

    protected function getOwnerTargetEntity($ownerType)
    {
        switch ($ownerType) {
            case 'ORGANIZATION':
                return 'Oro\Bundle\OrganizationBundle\Entity\Organization';
            case 'BUSINESS_UNIT':
                return 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';
            case 'USER':
                return 'Oro\Bundle\UserBundle\Entity\User';
        }

        throw new \InvalidArgumentException(sprintf('Unexpected owner type: %s.', $ownerType));
    }

    protected function updateFieldConfig(
        ConfigManager $configManager,
        $scope,
        $className,
        $ownerFieldName,
        array $values
    ) {
        $configProvider = $configManager->getProvider($scope);
        $fieldConfig    = $configProvider->getConfig($className, $ownerFieldName);
        foreach ($values as $code => $val) {
            $fieldConfig->set($code, $val);
        }
        $configManager->persist($fieldConfig);
        $configManager->calculateConfigChangeSet($fieldConfig);
    }
}
