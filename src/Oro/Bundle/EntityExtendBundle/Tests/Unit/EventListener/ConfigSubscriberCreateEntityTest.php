<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\EntityConfigEvent;
use Oro\Bundle\EntityExtendBundle\EventListener\ConfigSubscriber;

class ConfigSubscriberCreateEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test class is extend and persisted
     */
    public function testNewExtendEntity()
    {
        $configModel = new EntityConfigModel(
            'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass'
        );
        $entityConfig = new Config(
            new EntityConfigId(
                'extend',
                'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass'
            )
        );

        /**
         * value of NEW Config should be empty
         */
        $this->assertEquals(
            [],
            $entityConfig->all()
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

        $event = new EntityConfigEvent($configModel->getClassName(), $configManager);

        $extendManager = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\ExtendManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configSubscriber = new ConfigSubscriber($extendManager);
        $configSubscriber->updateEntityConfig($event);

        /** @var ConfigManager $cm */
        $cm = $event->getConfigManager();

        /**
         * value of persisted config should have:
         *  - is_extend -> true
         *  - extend_class -> 'Extend\Entity\ExtendTestClass'
         */
        $this->assertEquals(
            [
                'is_extend' => true,
                'extend_class' => 'Extend\Entity\ExtendTestClass'
            ],
            $entityConfig->all()
        );

        $this->assertObjectHasAttribute('persistConfigs', $cm);
        $this->assertAttributeSame(
            ['extend_Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass' => $entityConfig],
            'persistConfigs',
            $cm
        );
    }

    /**
     * Test class is NOT extend and should NOT be persisted
     */
    public function testNewNotExtendEntity()
    {
        $configModel = new EntityConfigModel(
            'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass2'
        );
        $entityConfig = new Config(
            new EntityConfigId(
                'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass2',
                'extend'
            )
        );

        /**
         * value of NEW Config should be empty
         */
        $this->assertEquals(
            [],
            $entityConfig->all()
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

        $event = new EntityConfigEvent($configModel->getClassName(), $configManager);

        $extendManager = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\ExtendManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configSubscriber = new ConfigSubscriber($extendManager);
        $configSubscriber->updateEntityConfig($event);

        /** @var ConfigManager $cm */
        $cm = $event->getConfigManager();
        $this->assertObjectHasAttribute('persistConfigs', $cm);
        $this->assertAttributeEquals(
            null,
            'persistConfigs',
            $cm
        );
    }
}
