<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\RenameFieldEvent;
use Oro\Bundle\EntityExtendBundle\EventListener\ConfigSubscriber;

class ConfigSubscriberCreateFieldTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS_NAME = 'Oro\Bundle\UserBundle\Entity\User';

    public function testCreateNewField()
    {
        $entityConfig = new Config(
            new EntityConfigId('extend', self::ENTITY_CLASS_NAME)
        );

        //value of Config should be empty
        $this->assertEmpty($entityConfig->all());

        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS_NAME)
            ->will($this->returnValue($entityConfig));
        $configProvider
            ->expects($this->never())
            ->method('persist');

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($configProvider));

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new FieldConfigEvent(self::ENTITY_CLASS_NAME, 'testField', $configManager);

        $configSubscriber = new ConfigSubscriber($extendConfigProvider);
        $configSubscriber->newFieldConfig($event);

        /** @var ConfigManager $cm */
        $cm = $event->getConfigManager();

        $this->assertObjectHasAttribute('persistConfigs', $cm);
        $this->assertAttributeSame(null, 'persistConfigs', $cm);
    }


    public function testUpdateNewField()
    {
        $entityConfig = new Config(
            new EntityConfigId('extend', self::ENTITY_CLASS_NAME)
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
            ->with(self::ENTITY_CLASS_NAME)
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

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new FieldConfigEvent(self::ENTITY_CLASS_NAME, 'testField', $configManager);

        $configSubscriber = new ConfigSubscriber($extendConfigProvider);
        $configSubscriber->newFieldConfig($event);

        /** @var ConfigManager $cm */
        $cm = $event->getConfigManager();

        $this->assertEquals(
            ['upgradeable' => true],
            $entityConfig->all()
        );
        $this->assertObjectHasAttribute('persistConfigs', $cm);
    }

    /**
     * Test new index created and old deleted when field renamed
     */
    public function testRenameField()
    {
        $entityConfig = new Config(
            new EntityConfigId('extend', self::ENTITY_CLASS_NAME)
        );
        $entityConfig->set(
            'index',
            [
                'testField' => ['testField'],
            ]
        );

        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS_NAME)
            ->will($this->returnValue($entityConfig));

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($configProvider));

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new RenameFieldEvent(self::ENTITY_CLASS_NAME, 'testField', 'newName', $configManager);

        $configSubscriber = new ConfigSubscriber($extendConfigProvider);
        $configSubscriber->renameField($event);


        $this->assertEquals(
            ['newName' => ['testField']],
            $entityConfig->get('index')
        );
    }
}
