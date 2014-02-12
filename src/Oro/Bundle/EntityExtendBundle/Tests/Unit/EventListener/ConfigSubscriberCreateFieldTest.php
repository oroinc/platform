<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EventListener\ConfigSubscriber;

class ConfigSubscriberCreateFieldTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateNew()
    {
        $configModel = new FieldConfigModel('testField', 'string');
        $entityConfig = new Config(
            new EntityConfigId('Oro\Bundle\UserBundle\Entity\User', 'extend')
        );

        //value of Config should be empty
        $this->assertEmpty($entityConfig->all());

        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($entityConfig));
        $configProvider
            ->expects($this->exactly(0))
            ->method('persist');

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($configProvider));

        $extendManager = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\ExtendManager')
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Event\NewFieldConfigModelEvent')
            ->setConstructorArgs([$configModel, $configManager])
            ->setMethods(['getClassName'])
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue('Oro\Bundle\UserBundle\Entity\User'));

        $configSubscriber = new ConfigSubscriber($extendManager);
        $configSubscriber->newField($event);

        /** @var ConfigManager $cm */
        $cm = $event->getConfigManager();

        $this->assertObjectHasAttribute('persistConfigs', $cm);
        $this->assertAttributeSame(null, 'persistConfigs', $cm);
    }


    public function testUpdateNew()
    {
        $configModel = new FieldConfigModel('testField', 'string');
        $entityConfig = new Config(
            new EntityConfigId('Oro\Bundle\UserBundle\Entity\User', 'extend')
        );
        $entityConfig->set('upgradeable', false);

        $this->assertEquals(
            ['upgradeable' => false],
            $entityConfig->all()
        );

        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($entityConfig));
        $configProvider
            ->expects($this->once())
            ->method('persist');

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($configProvider));

        $extendManager = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\ExtendManager')
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Event\NewFieldConfigModelEvent')
            ->setConstructorArgs([$configModel, $configManager])
            ->setMethods(['getClassName'])
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue('Oro\Bundle\UserBundle\Entity\User'));

        $configSubscriber = new ConfigSubscriber($extendManager);
        $configSubscriber->newField($event);

        /** @var ConfigManager $cm */
        $cm = $event->getConfigManager();


        $this->assertEquals(
            ['upgradeable' => true],
            $entityConfig->all()
        );
        $this->assertObjectHasAttribute('persistConfigs', $cm);
    }
}
