<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityExtendBundle\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ConfigSubscriberPersistConfigTest extends ConfigSubscriberTestCase
{
    /** @var  ConfigSubscriber */
    protected $configSubscriber;

    /** @var PersistConfigEvent */
    protected $event;

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                Events::PRE_PERSIST_CONFIG   => 'persistConfig',
                Events::NEW_ENTITY_CONFIG    => 'updateEntityConfig',
                Events::UPDATE_ENTITY_CONFIG => 'updateEntityConfig',
                Events::NEW_FIELD_CONFIG     => 'newFieldConfig',
                Events::RENAME_FIELD         => 'renameField',
            ],
            ConfigSubscriber::getSubscribedEvents()
        );
    }

    /**
     * Test that persistConfig called with event
     * that has config id something other than FieldConfigId
     */
    public function testWrongConfigId()
    {
        $this->configProvider->expects($this->never())
            ->method($this->anything());

        $entityConfigId = new EntityConfigId('extend', 'TestClass');
        $eventConfig    = new Config($entityConfigId);

        $event = new PersistConfigEvent($eventConfig, $this->configManager);
        $configSubscriber = new ConfigSubscriber();

        $configSubscriber->persistConfig($event);
    }

    /**
     *  Test create new field (entity state is 'NEW', owner - Custom)
     *  Nothing should be persisted
     */
    public function testScopeExtendNewFieldNewEntity()
    {
        $entityConfig = $this->getEntityConfig();
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
        $this->configManager->calculateConfigChangeSet($entityConfig);
        $this->configManager->calculateConfigChangeSet($fieldConfig);

        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($entityConfig));

        $this->event = new PersistConfigEvent($fieldConfig, $this->configManager);

        $this->configSubscriber = new ConfigSubscriber();
        $this->configSubscriber->persistConfig($this->event);

        $this->assertEquals(
            [],
            $this->configManager->getConfigChangeSet($entityConfig)
        );
    }

    /**
     *  Test create new field (entity state is 'Active')
     *  ConfigManager should have persisted 'extend_TestClass' with state 'Requires update'
     */
    public function testScopeExtendNewFieldActiveEntity()
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
        $this->configManager->calculateConfigChangeSet($entityConfig);
        $this->configManager->calculateConfigChangeSet($fieldConfig);

        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($entityConfig));

        $this->event = new PersistConfigEvent($fieldConfig, $this->configManager);

        $this->configSubscriber = new ConfigSubscriber();
        $this->configSubscriber->persistConfig($this->event);

        $this->assertEquals(
            ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
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
