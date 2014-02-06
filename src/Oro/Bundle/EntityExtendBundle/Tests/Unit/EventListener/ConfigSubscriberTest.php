<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EventListener\ConfigSubscriber;
use Oro\Bundle\EntityExtendBundle\Extend\ExtendManager;

class ConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ConfigSubscriber */
    protected $configSubscriber;

    public function setUp()
    {
        parent::setUp();

        $extendManager = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\ExtendManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configSubscriber = new ConfigSubscriber($extendManager);
    }

    public function test()
    {
        $fieldConfigId = new FieldConfigId('TestClass', 'extend', 'testFieldName', 'string');
        $eventConfig   = new Config($fieldConfigId);
        $eventConfig->setValues(
            [
                'owner'       => 'Custom',
                'is_extend'   => true,
                'state'       => 'New',
                'is_deleted'  => false,
                'upgradeable' => false,
                'relation'    => [],
                'schema'      => []
            ]
        );

        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            //->setMethods(['getConfigById'])
            ->getMock();
        $configProvider
            ->expects($this->any())
            ->method('getConfigById')
            ->will($this->returnValue($eventConfig));
        $configProvider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($eventConfig));

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $configManager
            ->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($configProvider));
        $configManager
            ->expects($this->any()) //TODO
            ->method('getConfigChangeSet')
            ->with($eventConfig)
            ->will(
                $this->returnValue(
                    [
                        'owner'     => [0 => null, 1 => 'Custom'],
                        'is_extend' => [0 => null, 1 => true],
                        'state'     => [0 => null, 1 => 'New']
                    ]
                )
            );

        $event = new PersistConfigEvent($eventConfig, $configManager);


        //$extendConfigProvider = $event->getConfigManager()->getProvider('extend');
        //var_dump(1, $extendConfigProvider);

        //$extendFieldConfig = $extendConfigProvider->getConfigById($event->getConfig()->getId());
        //var_dump(2, $extendFieldConfig);

        $this->configSubscriber->persistConfig($event);
    }

    public function testFindRelationKey()
    {

    }
} 