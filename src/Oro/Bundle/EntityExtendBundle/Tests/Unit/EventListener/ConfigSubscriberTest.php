<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\Events;

use Oro\Bundle\EntityExtendBundle\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityExtendBundle\Extend\ExtendManager;

class ConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ConfigSubscriber */
    protected $configSubscriber;

    protected $event;

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
    public function testPersistConfigScopeExtendNewFieldNewEntity()
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
    public function testPersistConfigScopeExtendNewFieldActiveEntity()
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
    public function testPersistConfigScopeExtendActiveFieldActiveEntity()
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
     *  Test create new field (relation type [1:*])
     */
    public function testPersistConfigScopeExtendRelationTypeCreateSelfRelationOneToMany()
    {
        $this->runPersistConfig(
            $this->getEventConfigNewField(
                [
                    'state' => ExtendManager::STATE_NEW,
                    'target_entity' => 'Oro\Bundle\UserBundle\Entity\User',
                ],
                'oneToMany'
            ),
            $this->getEntityConfig(['state' => ExtendManager::STATE_ACTIVE]),
            ['state' => [0 => null, 1 => ExtendManager::STATE_NEW]]
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();

        $this->assertAttributeEquals(
            [
                'extend_TestClass' => $this->getEntityConfig(
                    [
                        'state' => ExtendManager::STATE_UPDATED,
                        'relation' => [
                            'oneToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName' => [
                                'assign' => false,
                                'field_id' => new FieldConfigId(
                                    'Oro\Bundle\UserBundle\Entity\User',
                                    'extend',
                                    'testclass_testFieldName',
                                    'manyToOne'
                                ),
                                'owner' => true,
                                'target_entity' => 'TestClass',
                                'target_field_id' => new FieldConfigId('TestClass', 'extend', 'testFieldName', 'oneToMany'),
                            ]
                        ]
                    ]
                )
            ],
            'persistConfigs',
            $cm
        );
    }

    /**
     *  Test create new field (relation type [*:1])
     */
    public function testPersistConfigScopeExtendRelationTypeCreateSelfRelationManyToOne()
    {
        $this->runPersistConfig(
            $this->getEventConfigNewField(
                [
                    'state' => ExtendManager::STATE_NEW,
                    'target_entity' => 'Oro\Bundle\UserBundle\Entity\User',
                ],
                'manyToOne'
            ),
            $this->getEntityConfig(['state' => ExtendManager::STATE_ACTIVE]),
            ['state' => [0 => null, 1 => ExtendManager::STATE_NEW]]
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();

        $this->assertAttributeEquals(
            [
                'extend_TestClass' => $this->getEntityConfig(
                    [
                        'state' => ExtendManager::STATE_UPDATED,
                        'relation' => [
                            'manyToOne|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName' => [
                                'assign' => false,
                                'field_id' => false,
                                'owner' => false,
                                'target_entity' => 'TestClass',
                                'target_field_id' => new FieldConfigId('TestClass', 'extend', 'testFieldName', 'manyToOne'),
                            ]
                        ]
                    ]
                )
            ],
            'persistConfigs',
            $cm
        );
    }

    /**
     *  Test create new field (relation type [*:*])
     */
    public function testPersistConfigScopeExtendRelationTypeCreateSelfRelationManyToMany()
    {
        $this->runPersistConfig(
            $this->getEventConfigNewField(
                [
                    'state' => ExtendManager::STATE_NEW,
                    'target_entity' => 'Oro\Bundle\UserBundle\Entity\User',
                ],
                'manyToMany'
            ),
            $this->getEntityConfig(['state' => ExtendManager::STATE_ACTIVE]),
            ['state' => [0 => null, 1 => ExtendManager::STATE_NEW]]
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();

        $this->assertAttributeEquals(
            [
                'extend_TestClass' => $this->getEntityConfig(
                    [
                        'state' => ExtendManager::STATE_UPDATED,
                        'relation' => [
                            'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName' => [
                                'assign' => false,
                                'field_id' => new FieldConfigId(
                                        'Oro\Bundle\UserBundle\Entity\User',
                                        'extend',
                                        'testclass_testFieldName',
                                        'manyToMany'
                                    ),
                                'owner' => false,
                                'target_entity' => 'TestClass',
                                'target_field_id' => new FieldConfigId('TestClass', 'extend', 'testFieldName', 'manyToMany'),
                            ]
                        ]
                    ]
                )
            ],
            'persistConfigs',
            $cm
        );
    }

    /**
     *  Test create new relation field to own entity
     *  Should NOT be persisted
     */
    public function testPersistConfigScopeExtendRelationTypeOwnEntity()
    {
        $this->runPersistConfig(
            $this->getEventConfigNewField([], 'oneToMany'),
            $test = $this->getEntityConfig(
                [
                    'state' => ExtendManager::STATE_NEW,
                    'relation' => [
                        'oneToMany|TestClass|TestClass|testFieldName' => [
                            'assign' => false,
                            'field_id' => new FieldConfigId('TestClass', 'extend', 'testFieldName', 'oneToMany'),
                            'owner' => true,
                            'target_entity' => 'Oro\Bundle\UserBundle\Entity\User',
                        ]
                    ],

                ]
            ),
            [
                'state' => [0 => null, 1 => ExtendManager::STATE_NEW]
            ]
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();
        $this->assertAttributeEquals(null, 'persistConfigs', $cm);
    }

    /**
     *  Field should be added to index
     */
    public function testPersistConfigScopeDataGridNewFieldNewEntity()
    {
        $this->runPersistConfig(
            $this->getEventConfigNewField([], 'integer', 'datagrid'),
            $this->getEntityConfig(),
            ['is_visible' => [0 => null, 1 => true]]
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();
        $this->assertAttributeEquals(
            ['extend_TestClass' => $this->getEntityConfig(['index' => ['testFieldName' => null]])],
            'persistConfigs',
            $cm
        );
    }

    /**
     *  Field type 'text' should NOT be added to index
     */
    public function testPersistConfigScopeDataGridNewFieldNewEntityNot()
    {
        $this->runPersistConfig(
            $this->getEventConfigNewField([], 'text', 'datagrid'),
            $this->getEntityConfig(),
            ['is_visible' => [0 => null, 1 => true]]
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();
        $this->assertAttributeEquals(null, 'persistConfigs', $cm);
    }

    /**
     * FieldConfig
     *
     * @param array $values
     * @param string $type
     * @param string $scope
     * @return Config
     */
    protected function getEventConfigNewField($values = [], $type = 'string', $scope = 'extend')
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

        $fieldConfigId = new FieldConfigId('TestClass', $scope, 'testFieldName', $type);
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
            'owner'       => ExtendManager::OWNER_CUSTOM,
            'is_extend'   => true,
            'state'       => ExtendManager::STATE_NEW,
            'is_deleted'  => false,
            'upgradeable' => false,
            'relation'    => [],
            'schema'      => [],
            'index'       => []
        ];

        if (count($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        $entityConfigId = new EntityConfigId('TestClass', $scope);
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

        $extendManager = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\ExtendManager')
            ->disableOriginalConstructor()
            ->getMock();

        $extendConfigProvider = clone $configProvider;
        $extendConfigProvider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($eventConfig));

        $extendManager
            ->expects($this->any())
            ->method('getConfigProvider')
            ->will($this->returnValue($extendConfigProvider));

        $this->configSubscriber = new ConfigSubscriber($extendManager);
        $this->configSubscriber->persistConfig($this->event);
    }
}
