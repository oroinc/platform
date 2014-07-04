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
     */
    public function testScopeExtendRelationTypeCreateTargetRelationManyToMany()
    {
        $fieldConfigId = new FieldConfigId('extend', 'TestClass', 'rel', 'manyToMany');
        $relationKey   = 'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|rel';
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
                'relation_key'    => $relationKey
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
                        'assign'          => true,
                        'owner'           => true,
                        'target_entity'   => 'Oro\Bundle\UserBundle\Entity\User',
                        'field_id'        => new FieldConfigId(
                            'extend',
                            'TestEntity',
                            'rel',
                            'manyToMany'
                        )
                    ]
                ],
                'schema'      => [],
                'index'       => []
            ]
        );

        $this->runPersistConfig(
            $eventConfig,
            $selfEntityConfig,
            ['state' => [0 => ExtendScope::STATE_ACTIVE, 1 => ExtendScope::STATE_UPDATED ]]
        );

        /** @var ConfigManager $cm */
        $cm = $this->event->getConfigManager();

        $persistedRelation = $cm->getProvider('extend')->getConfig('TestClass')->get('relation');
        $this->assertInstanceOf(
            'Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId',
            $persistedRelation[$relationKey]['field_id']
        );
        $this->assertInstanceOf(
            'Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId',
            $persistedRelation[$relationKey]['target_field_id']
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
        $configProvider
            ->expects($this->any())
            ->method('persist')
            ->will(
                $this->returnCallback(
                    function (Config $item) {
                        if ($item->has('relation')) {
                            $relations = $item->get('relation');
                            foreach ($relations as $relation) {
                                $this->assertInstanceOf(
                                    'Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId',
                                    $relation['field_id']
                                );
                                $this->assertInstanceOf(
                                    'Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId',
                                    $relation['target_field_id']
                                );
                            }
                        }
                    }
                )
            );

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
