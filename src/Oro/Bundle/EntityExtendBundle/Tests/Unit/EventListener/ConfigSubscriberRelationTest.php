<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityExtendBundle\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityExtendBundle\Extend\ExtendManager;

use Oro\Bundle\EntityConfigBundle\Event\Events;

class ConfigSubscriberRelationTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ConfigSubscriber */
    protected $configSubscriber;

    protected $event;

    /**
     *  Test create new field (relation type [*:*])
     */
    public function testPersistConfig_ScopeExtend_RelationType_createTargetRelation_manyToMany()
    {
        $fieldConfigId = new FieldConfigId('TestClass', 'extend', 'rel', 'manyToMany');
        $eventConfig   = new Config($fieldConfigId);
        $eventConfig->setValues(
            [
                'owner'           => ExtendManager::OWNER_CUSTOM,
                'state'           => ExtendManager::STATE_NEW,
                'is_extend'       => true,
                'target_title'    => ['username'],
                'target_grid'     => ['username'],
                'target_entity'   => 'Oro\Bundle\UserBundle\Entity\User',
                'target_field'    => ['username'],
                'target_detailed' => ['username'],
                'relation_key'    => 'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|rel'
            ]
        );

        $selfEntityConfigId = new EntityConfigId('TestClass', 'extend');
        $selfEntityConfig   = new Config($selfEntityConfigId);
        $selfEntityConfig->setValues(
            [
                'owner'       => ExtendManager::OWNER_CUSTOM,
                'state'       => ExtendManager::STATE_NEW,
                'is_extend'   => true,
                'is_deleted'  => false,
                'upgradeable' => false,
                'relation'    => [
                    'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|rel' => [
                        'assign'          => false,
                        'owner'           => true,
                        'target_entity'   => 'Oro\Bundle\UserBundle\Entity\User',
                        'field_id'        => new FieldConfigId(
                            'TestEntity',
                            'extend',
                            'rel',
                            'manyToMany'
                        ),
                        'target_field_id' => new FieldConfigId(
                            'Oro\Bundle\UserBundle\Entity\User',
                            'extend',
                            'testclass_rel',
                            'manyToMany'
                        ),
                    ]
                ],
                'schema'      => [],
                'index'       => []
            ]
        );

        $targetEntityConfigId = new EntityConfigId('Oro\Bundle\UserBundle\Entity\User', 'extend');
        $targetEntityConfig   = new Config($targetEntityConfigId);
        $targetEntityConfig->setValues(
            [
                'owner'       => ExtendManager::OWNER_SYSTEM,
                'state'       => ExtendManager::STATE_ACTIVE,
                'is_extend'   => true,
                'is_deleted'  => false,
                'upgradeable' => false,
                'relation'    => [
                    'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|rel' => [
                        'assign'          => false,
                        'owner'           => true,
                        'target_entity'   => 'Oro\Bundle\UserBundle\Entity\User',
                        'field_id'        => new FieldConfigId(
                            'TestEntity',
                            'extend',
                            'rel',
                            'manyToMany'
                        ),
                        'target_field_id' => new FieldConfigId(
                            'Oro\Bundle\UserBundle\Entity\User',
                            'extend',
                            'testclass_rel',
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
            ['state' => [0 => null, 1 => ExtendManager::STATE_NEW]]
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();
        //print_r($cm->persistConfigs);

        /*
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
            ->setMethods(['getProvider', 'getConfigChangeSet'])
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
