<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityExtendBundle\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityExtendBundle\Extend\ExtendManager;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\Constraints\Null;

class ConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ConfigSubscriber */
    protected $configSubscriber;

    protected $event;

    public function setUp()
    {
        parent::setUp();

        $extendManager = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\ExtendManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configSubscriber = new ConfigSubscriber($extendManager);
    }


    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                Events::PRE_PERSIST_CONFIG         => 'persistConfig',
                Events::NEW_ENTITY_CONFIG_MODEL    => 'newEntity',
                Events::UPDATE_ENTITY_CONFIG_MODEL => 'newEntity',
                Events::NEW_FIELD_CONFIG_MODEL     => 'newField',
            ],
            ConfigSubscriber::getSubscribedEvents()
        );
    }

    /**
     *  Test create new field (entity state is 'NEW')
     *  Nothing should be persisted
     *  ConfigManager should have persisted 'extend_TestClass' with state 'Requires update'
     */
    public function testPersistConfig_ScopeExtend_NewField_NewEntity()
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
    public function testPersistConfig_ScopeExtend_NewField_ActiveEntity()
    {
        $this->runPersistConfig(
            $this->getEventConfigNewField(),
            $this->getEntityConfig(['state' => ExtendManager::STATE_ACTIVE]),
            $this->getChangeSet()
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();
        $this->assertAttributeEquals(
            ['extend_TestClass' => $this->getEntityConfig(['state' => ExtendManager::STATE_UPDATED])],
            'persistConfigs',
            $cm
        );
    }

    /**
     *  Test update active field (entity state is 'Active')
     *  ConfigManager should have persisted 'extend_TestClass' with state 'Requires update'
     */
    public function testPersistConfig_ScopeExtend_ActiveField_ActiveEntity()
    {
        $this->runPersistConfig(
            $this->getEventConfigNewField(['state' => ExtendManager::STATE_ACTIVE]),
            $this->getEntityConfig(['state' => ExtendManager::STATE_ACTIVE]),
            [
                'length' => [0 => 255, 1 => 200]
            ]
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();

        $this->assertAttributeEquals(
            ['extend_TestClass' => $this->getEntityConfig(['state' => ExtendManager::STATE_UPDATED])],
            'persistConfigs',
            $cm
        );

        $this->assertAttributeEquals(
            [
                'extend_TestClass_testFieldName' => [
                    'owner'     => [0 => null, 1 => ExtendManager::OWNER_CUSTOM],
                    'state'     => [0 => null, 1 => ExtendManager::STATE_UPDATED],
                    'is_extend' => [0 => null, 1 => true]
                ]
            ],
            'configChangeSets',
            $cm
        );
    }

    /**
     *  Test create new field (relation type [1:*, *:*, *:1])
     *  ConfigManager should have persisted 'extend_TestClass' with state 'Requires update'
     */
    public function testPersistConfig_ScopeExtend_RelationType()
    {return;
        $this->runPersistConfig(
            $this->getEventConfigNewField(
                ['state' => ExtendManager::STATE_NEW],
                'oneToMany'
            ),
            $this->getEntityConfig(
                ['state' => ExtendManager::STATE_ACTIVE]
            ),
            [
                'state' => [0 => null, 1 => ExtendManager::STATE_NEW]
            ]
        );

        //var_dump($this->configSubscriber);
        //var_dump($this->event->getConfig()->getId()->getClassName());

        /** @var ConfigManager $cm */
        //$cm = $this->event->getConfigManager();

        /*$this->assertAttributeEquals(
            ['extend_TestClass' => $this->getEntityConfig(['state' => ExtendManager::STATE_UPDATED])],
            'persistConfigs',
            $cm
        );*/
    }

    /**
     * FieldConfig
     *
     * @param array $values
     * @param string $type
     * @return Config
     */
    protected function getEventConfigNewField($values = [], $type = 'string')
    {
        $resultValues = [
            'owner'      => ExtendManager::OWNER_CUSTOM,
            'state'      => ExtendManager::STATE_NEW,
            'is_extend'  => true,
            'is_deleted' => false,
        ];

        if (count($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        //var_dump($resultValues);

        $fieldConfigId = new FieldConfigId('TestClass', 'extend', 'testFieldName', $type);
        $eventConfig   = new Config($fieldConfigId);
        $eventConfig->setValues($resultValues);

        return $eventConfig;
    }

    /**
     * EntityConfig
     *
     * @param array $values
     * @return Config
     */
    protected function getEntityConfig($values = [])
    {
        $resultValues = [
            'owner'       => ExtendManager::OWNER_CUSTOM,
            'is_extend'   => true,
            'state'       => ExtendManager::STATE_NEW,
            'is_deleted'  => false,
            'upgradeable' => false,
            'relation'    => [],
            'schema'      => []
        ];

        if (count($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        $entityConfigId = new EntityConfigId('TestClass', 'extend');
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues($resultValues);

        return $entityConfig;
    }

    protected function getChangeSet($values = [])
    {
        return array_merge(
            [
                'owner'     => [0 => null, 1 => ExtendManager::OWNER_CUSTOM],
                'is_extend' => [0 => null, 1 => true],
                'state'     => [0 => null, 1 => ExtendManager::STATE_NEW]
            ],
            $values
        );
    }

    protected function runPersistConfig($eventConfig, $entityConfig, $changeSet)//, $scope = 'extend')
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider
            ->expects($this->any())
            ->method('getConfig')
            ->with($this->equalTo('TestClass'))
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
        $this->configSubscriber->persistConfig($this->event);


/*
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->setMethods(['getProviderBag', 'getProvider', 'getConfigChangeSet'])
            //->setMethods(null)
            ->getMock();

        $configProvider = new ConfigProvider($configManager, $container, $scope, []);

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
        /*$configManager
            ->expects($this->any())
            ->method('checkDatabase')
            ->will($this->returnValue(true));
*/


        //$extendManager          = new ExtendManager($configProvider);
        //$this->configSubscriber = new ConfigSubscriber($extendManager);
        //$this->event            = new PersistConfigEvent($eventConfig, $configManager);

        //var_dump($extendManager);

        //$this->configSubscriber->persistConfig($this->event);
    }

    public function testFindRelationKey()
    {

    }
} 