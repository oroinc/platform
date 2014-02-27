<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityExtendBundle\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ConfigSubscriberRelationTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ConfigSubscriber */
    protected $configSubscriber;

    protected $event;

    /**
     * Test create new field (relation type [*:*])
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testPersistConfigScopeExtendRelationTypeCreateTargetRelationManyToMany()
    {
        $fieldConfigId = new FieldConfigId('extend', 'TestClass', 'rel', 'manyToMany');
        $eventConfig   = new Config($fieldConfigId);
        $eventConfig->setValues(
            [
                'owner'           => ExtendScope::OWNER_CUSTOM,
                'state'           => ExtendScope::STATE_NEW,
                'is_extend'       => true,
                'target_entity'   => 'Oro\Bundle\UserBundle\Entity\User',
                'target_title'    => ['username'],
                'target_grid'     => ['username'],
                'target_detailed' => ['username'],
                'relation_key'    => 'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|rel'
            ]
        );

        $selfEntityConfigId = new EntityConfigId('extend', 'TestClass');
        $selfEntityConfig   = new Config($selfEntityConfigId);
        $selfEntityConfig->setValues(
            [
                'owner'       => ExtendScope::OWNER_CUSTOM,
                'state'       => ExtendScope::STATE_NEW,
                'is_extend'   => true,
                'is_deleted'  => false,
                'upgradeable' => false,
                'relation'    => [
                    'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|rel' => [
                        'assign'          => false,
                        'owner'           => true,
                        'target_entity'   => 'Oro\Bundle\UserBundle\Entity\User',
                        'field_id'        => new FieldConfigId(
                            'extend',
                            'TestEntity',
                            'rel',
                            'manyToMany'
                        ),
                        'target_field_id' => new FieldConfigId(
                            'extend',
                            'Oro\Bundle\UserBundle\Entity\User',
                            'testclass_rel',
                            'manyToMany'
                        ),
                    ]
                ],
                'schema'      => [],
                'index'       => []
            ]
        );

        $targetEntityConfigId = new EntityConfigId('extend', 'Oro\Bundle\UserBundle\Entity\User');
        $targetEntityConfig   = new Config($targetEntityConfigId);
        $targetEntityConfig->setValues(
            [
                'owner'       => ExtendScope::OWNER_SYSTEM,
                'state'       => ExtendScope::STATE_ACTIVE,
                'is_extend'   => true,
                'is_deleted'  => false,
                'upgradeable' => false,
                'relation'    => [
                    'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|rel' => [
                        'assign'          => false,
                        'owner'           => false,
                        'target_entity'   => 'TestClass',
                        'field_id'        => new FieldConfigId(
                            'extend',
                            'Oro\Bundle\UserBundle\Entity\User',
                            'testclass_rel',
                            'manyToMany'
                        ),
                        'target_field_id' => new FieldConfigId(
                            'extend',
                            'TestEntity',
                            'rel',
                            'manyToMany'
                        ),
                    ]
                ],
                'schema'      => [],
                'index'       => []
            ]
        );

        $this->runPersistConfig(
            $eventConfig,
            $selfEntityConfig,
            ['state' => [0 => null, 1 => ExtendScope::STATE_NEW]]
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();
        /*
        $this->assertAttributeEquals(
            [
                'extend_TestClass' => $this->getEntityConfig(
                        [
                            'state' => ExtendScope::STATE_UPDATED,
                            'relation' => [
                                'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName' => [
                                    'assign' => false,
                                    'field_id' => new FieldConfigId(
                                            'extend',
                                            'Oro\Bundle\UserBundle\Entity\User',
                                            'testclass_testFieldName',
                                            'manyToMany'
                                        ),
                                    'owner' => false,
                                    'target_entity' => 'TestClass',
                                    'target_field_id' => new FieldConfigId(
                                            'extend',
                                             'TestClass',
                                             'testFieldName',
                                             'manyToMany'
                                        ),
                                ]
                            ]
                        ]
                    )
            ],
            'persistConfigs',
            $cm
        );
        */
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
