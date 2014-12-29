<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\Events;

use Oro\Bundle\EntityExtendBundle\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ConfigSubscriberPersistConfigTest extends \PHPUnit_Framework_TestCase
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
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider->expects($this->never())
            ->method($this->anything());

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->never())
            ->method($this->anything());

        $entityConfigId = new EntityConfigId('extend', 'TestClass');
        $eventConfig    = new Config($entityConfigId);

        $event = new PersistConfigEvent($eventConfig, $configManager);
        $configSubscriber = new ConfigSubscriber($configProvider);

        $configSubscriber->persistConfig($event);
    }

    /**
     *  Test create new field (entity state is 'NEW', owner - Custom)
     *  Nothing should be persisted
     */
    public function testScopeExtendNewFieldNewEntity()
    {
        $this->runPersistConfig(
            $this->getEventConfigNewField(),
            $this->getEntityConfig(),
            $this->getChangeSet()
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();

        $this->assertAttributeSame(null, 'persistConfigs', $cm);
    }

    /**
     *  Test create new field (entity state is 'Active')
     *  ConfigManager should have persisted 'extend_TestClass' with state 'Requires update'
     */
    public function testScopeExtendNewFieldActiveEntity()
    {
        $this->runPersistConfig(
            $this->getEventConfigNewField(),
            $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]),
            $this->getChangeSet()
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();
        $this->assertAttributeEquals(
            ['extend_TestClass' => $this->getEntityConfig(['state' => ExtendScope::STATE_UPDATE])],
            'persistConfigs',
            $cm
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

        if (count($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        $entityConfigId = new EntityConfigId($scope, 'TestClass');
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues($resultValues);

        return $entityConfig;
    }

    protected function getChangeSet($values = [])
    {
        return array_merge(
            [
                'owner'     => [0 => null, 1 => ExtendScope::OWNER_CUSTOM],
                'is_extend' => [0 => null, 1 => true],
                'state'     => [0 => null, 1 => ExtendScope::STATE_NEW]
            ],
            $values
        );
    }

    protected function runPersistConfig($eventConfig, $entityConfig, $changeSet)
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($entityConfig));
        $configProvider
            ->expects($this->any())
            ->method('getConfigById')
            ->will($this->returnValue($eventConfig));

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->setMethods(['getProviderBag', 'getProvider', 'getConfigChangeSet'])
            ->getMock();
        $configManager
            ->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($configProvider));
        $configManager
            ->expects($this->any())
            ->method('getConfigChangeSet')
            ->with($eventConfig)
            ->will($this->returnValue($changeSet));

        $this->event = new PersistConfigEvent($eventConfig, $configManager);

        $extendConfigProvider = clone $configProvider;
        $extendConfigProvider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($eventConfig));

        $this->configSubscriber = new ConfigSubscriber($extendConfigProvider);
        $this->configSubscriber->persistConfig($this->event);
    }
}
