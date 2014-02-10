<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;

use Oro\Bundle\EntityConfigBundle\Event\NewEntityConfigModelEvent;

use Oro\Bundle\EntityExtendBundle\EventListener\ConfigSubscriber;

class ConfigSubscriberCreateTest extends \PHPUnit_Framework_TestCase
{
    public function testNewEntity()
    {
        $configModel = new EntityConfigModel(
            'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass'
        );
        $entityConfig = new Config(
            new EntityConfigId(
                'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass',
                'extend'
            )
        );

        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($entityConfig));

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->setMethods(['getProvider'])
            ->getMock();
        $configManager
            ->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($configProvider));

        $event = new NewEntityConfigModelEvent($configModel, $configManager);

        $extendManager = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\ExtendManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configSubscriber = new ConfigSubscriber($extendManager);
        $configSubscriber->newEntity($event);

        /** @var ConfigManager $cm */
        $cm = $event->getConfigManager();

        $this->assertObjectHasAttribute('persistConfigs', $cm);
        $this->assertAttributeSame(
            ['extend_Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass' => $entityConfig],
            'persistConfigs',
            $cm
        );
    }
}
