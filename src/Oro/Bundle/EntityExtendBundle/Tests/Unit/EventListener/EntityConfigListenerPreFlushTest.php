<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreSetRequireUpdateEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\EventListener\EntityConfigListener;

class EntityConfigListenerPreFlushTest extends EntityConfigListenerTestCase
{
    /**
     *  Test create new field (entity state is 'NEW', owner - Custom)
     *  Nothing should be persisted
     */
    public function testNewFieldNewEntity()
    {
        $entityConfig = $this->getEntityConfig();
        $fieldConfig = $this->getEventConfigNewField();

        // first call - add to originalValues
        // second call - call getEntityConfig in preFlush method
        $this->configCache->expects($this->exactly(2))
            ->method('getEntityConfig')
            ->willReturnOnConsecutiveCalls(clone $entityConfig, $entityConfig);
        $this->configManager->getEntityConfig(
            $entityConfig->getId()->getScope(),
            $entityConfig->getId()->getClassName()
        );

        $this->configManager->persist($entityConfig);
        $this->configManager->persist($fieldConfig);
        $this->configManager->calculateConfigChangeSet($entityConfig);
        $this->configManager->calculateConfigChangeSet($fieldConfig);

        $event = new PreFlushConfigEvent(['extend' => $fieldConfig], $this->configManager);

        $listener = new EntityConfigListener($this->eventDispatcher);
        $listener->preFlush($event);

        $this->assertEquals(
            [],
            $this->configManager->getConfigChangeSet($entityConfig)
        );
    }

    /**
     *  Test create new field (entity state is 'Active')
     *  ConfigManager should have persisted 'extend_TestClass' with state 'Requires update'
     */
    public function testNewFieldActiveEntityUpdateRequired()
    {
        $entityConfig = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);
        $fieldConfig = $this->getEventConfigNewField();

        // first call - add to originalValues
        // second call - call getEntityConfig in preFlush method
        $this->configCache->expects($this->exactly(2))
            ->method('getEntityConfig')
            ->willReturnOnConsecutiveCalls(clone $entityConfig, $entityConfig);
        $this->configManager->getEntityConfig(
            $entityConfig->getId()->getScope(),
            $entityConfig->getId()->getClassName()
        );

        $this->configManager->persist($entityConfig);
        $this->configManager->persist($fieldConfig);
        $this->configManager->calculateConfigChangeSet($entityConfig);
        $this->configManager->calculateConfigChangeSet($fieldConfig);

        $event = new PreFlushConfigEvent(['extend' => $fieldConfig], $this->configManager);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::PRE_SET_REQUIRE_UPDATE,
                new PreSetRequireUpdateEvent($event->getConfigs(), $this->configManager)
            )
            ->willReturnCallback(function (string $eventName, PreSetRequireUpdateEvent $event) {
                $event->setUpdateRequired(true);
            });

        $listener = new EntityConfigListener($this->eventDispatcher);
        $listener->preFlush($event);

        $this->configManager->calculateConfigChangeSet($entityConfig);

        $this->assertEquals(
            ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            $this->configManager->getConfigChangeSet($entityConfig)
        );
    }

    public function testNewFieldActiveEntityUpdateNotRequired()
    {
        $entityConfig = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);
        $fieldConfig = $this->getEventConfigNewField();

        $this->configCache->expects($this->once())
            ->method('getEntityConfig')
            ->willReturnOnConsecutiveCalls($entityConfig);
        $this->configManager->getEntityConfig(
            $entityConfig->getId()->getScope(),
            $entityConfig->getId()->getClassName()
        );

        $this->configManager->persist($entityConfig);
        $this->configManager->persist($fieldConfig);
        $this->configManager->calculateConfigChangeSet($entityConfig);
        $this->configManager->calculateConfigChangeSet($fieldConfig);

        $event = new PreFlushConfigEvent(['extend' => $fieldConfig], $this->configManager);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::PRE_SET_REQUIRE_UPDATE,
                new PreSetRequireUpdateEvent($event->getConfigs(), $this->configManager)
            )
            ->willReturnCallback(function (string $eventName, PreSetRequireUpdateEvent $event) {
                $event->setUpdateRequired(false);
            });

        $listener = new EntityConfigListener($this->eventDispatcher);
        $listener->preFlush($event);

        $this->configManager->calculateConfigChangeSet($entityConfig);

        $this->assertEquals(
            [],
            $this->configManager->getConfigChangeSet($entityConfig)
        );
    }

    /**
     *  Test flush new field (entity state is 'Active')
     *  The entity state should not be changed
     */
    public function testSavingNewFieldActiveEntity()
    {
        $entityConfig = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);
        $fieldConfig = $this->getEventConfigNewField();

        // add to originalValues
        $this->configCache->expects($this->once())
            ->method('getEntityConfig')
            ->willReturn(clone $entityConfig);
        $this->configManager->getEntityConfig(
            $entityConfig->getId()->getScope(),
            $entityConfig->getId()->getClassName()
        );

        $this->configManager->persist($entityConfig);
        $this->configManager->persist($fieldConfig);
        $fieldConfig->set('state', ExtendScope::STATE_ACTIVE);
        $this->configManager->persist($fieldConfig);
        $this->configManager->calculateConfigChangeSet($entityConfig);
        $this->configManager->calculateConfigChangeSet($fieldConfig);

        $event = new PreFlushConfigEvent(['extend' => $fieldConfig], $this->configManager);

        $listener = new EntityConfigListener($this->eventDispatcher);
        $listener->preFlush($event);

        $this->assertEquals(
            [],
            $this->configManager->getConfigChangeSet($entityConfig)
        );
    }

    /**
     * FieldConfig
     *
     * @param array $values
     * @param string $type
     * @param string $scope
     *
     * @return Config
     */
    protected function getEventConfigNewField($values = [], $type = 'string', $scope = 'extend')
    {
        $resultValues = [
            'owner'      => ExtendScope::OWNER_CUSTOM,
            'state'      => ExtendScope::STATE_NEW,
            'is_extend'  => true,
            'is_deleted' => false,
        ];

        if (count($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        $fieldConfigId = new FieldConfigId($scope, 'TestClass', 'testFieldName', $type);
        $eventConfig   = new Config($fieldConfigId);
        $eventConfig->setValues($resultValues);

        return $eventConfig;
    }

    /**
     * EntityConfig
     *
     * @param array $values
     * @param string $scope
     * @return Config
     */
    protected function getEntityConfig($values = [], $scope = 'extend')
    {
        $resultValues = [
            'owner'       => ExtendScope::OWNER_CUSTOM,
            'is_extend'   => true,
            'state'       => ExtendScope::STATE_NEW,
            'is_deleted'  => false,
            'upgradeable' => false,
            'relation'    => [],
            'schema'      => [],
            'index'       => []
        ];

        if (!empty($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        $entityConfigId = new EntityConfigId($scope, 'TestClass');
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues($resultValues);

        return $entityConfig;
    }
}
