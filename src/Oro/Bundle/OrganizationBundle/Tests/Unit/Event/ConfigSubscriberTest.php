<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Event;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\OrganizationBundle\Event\ConfigSubscriber;

class ConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigSubscriber */
    protected $subscriber;

    public function setUp()
    {
        $this->subscriber = new ConfigSubscriber();
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                Events::PRE_PERSIST_CONFIG => ['prePersistEntityConfig', 100]
            ],
            $this->subscriber->getSubscribedEvents()
        );
    }

    public function testPrePersistEntityConfigWithNoConfig()
    {
        $config = new Config(new EntityConfigId('ownership', 'Test\Entity1'));

        $configManager           = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $ownershipConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->once())
            ->method('getProvider')
            ->with('ownership')
            ->will($this->returnValue($ownershipConfigProvider));

        $ownershipConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue(false));
        $ownershipConfigProvider->expects($this->never())
            ->method('getConfig');

        $this->subscriber->prePersistEntityConfig(new PersistConfigEvent($config, $configManager));
    }

    public function testPrePersistEntityConfigForSystemEntityWithNotNoneOwnership()
    {
        $config = new Config(new EntityConfigId('ownership', 'Test\Entity1'));
        $config->set('owner_type', 'USER');

        $extendConfig = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $extendConfig->set('owner', ExtendScope::OWNER_SYSTEM);

        $configManager           = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $ownershipConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider    = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['ownership', $ownershipConfigProvider],
                        ['extend', $extendConfigProvider],
                    ]
                )
            );

        $ownershipConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue(true));
        $ownershipConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue($config));

        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue(true));
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue($extendConfig));

        $configManager->expects($this->never())->method('persist');
        $configManager->expects($this->never())->method('calculateConfigChangeSet');

        $this->subscriber->prePersistEntityConfig(new PersistConfigEvent($config, $configManager));
    }

    public function testPrePersistEntityConfigForCustomEntityDoesNotRequireUpdate()
    {
        $config = new Config(new EntityConfigId('ownership', 'Test\Entity1'));
        $config->set('owner_type', 'USER');
        $config->set('owner_field_name', 'owner');
        $config->set('owner_column_name', 'owner_id');
        $config->set('organization_field_name', 'organization');
        $config->set('organization_column_name', 'organization_id');

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $ownershipConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider    = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['ownership', $ownershipConfigProvider],
                        ['extend', $extendConfigProvider],
                    ]
                )
            );

        $ownershipConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue(true));
        $ownershipConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue($config));

        $configManager->expects($this->never())->method('persist');
        $configManager->expects($this->never())->method('calculateConfigChangeSet');

        $this->subscriber->prePersistEntityConfig(new PersistConfigEvent($config, $configManager));
    }

    public function testPrePersistEntityConfigForCustomEntityWithNotNoneOwnership()
    {
        $config = new Config(new EntityConfigId('ownership', 'Test\Entity1'));
        $config->set('owner_type', 'USER');

        $extendConfig = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);

        $configManager           = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $ownershipConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider    = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['ownership', $ownershipConfigProvider],
                        ['extend', $extendConfigProvider],
                    ]
                )
            );

        $ownershipConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue(true));
        $ownershipConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue($config));

        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue(true));
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue($extendConfig));

        $expectedConfig = clone $config;
        $expectedConfig->set('owner_field_name', 'owner');
        $expectedConfig->set('owner_column_name', 'owner_id');
        $expectedConfig->set('organization_field_name', 'organization');
        $expectedConfig->set('organization_column_name', 'organization_id');

        $configManager->expects($this->once())
            ->method('persist')
            ->with($expectedConfig);
        $configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($expectedConfig);

        $this->subscriber->prePersistEntityConfig(new PersistConfigEvent($config, $configManager));
    }

    public function testPrePersistEntityConfigWithNoneOwnership()
    {
        $config = new Config(new EntityConfigId('ownership', 'Test\Entity1'));
        $config->set('owner_type', 'NONE');

        $configManager           = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $ownershipConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->once())
            ->method('getProvider')
            ->with('ownership')
            ->will($this->returnValue($ownershipConfigProvider));

        $ownershipConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue(true));
        $ownershipConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue($config));

        $expectedConfig = new Config(new EntityConfigId('ownership', 'Test\Entity1'));

        $configManager->expects($this->once())
            ->method('persist')
            ->with($expectedConfig);
        $configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($expectedConfig);

        $this->subscriber->prePersistEntityConfig(new PersistConfigEvent($config, $configManager));
    }

    public function testPrePersistEntityConfigWithoutOwnership()
    {
        $config = new Config(new EntityConfigId('ownership', 'Test\Entity1'));

        $configManager           = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $ownershipConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->once())
            ->method('getProvider')
            ->with('ownership')
            ->will($this->returnValue($ownershipConfigProvider));

        $ownershipConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue(true));
        $ownershipConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue($config));

        $configManager->expects($this->never())
            ->method('persist');

        $this->subscriber->prePersistEntityConfig(new PersistConfigEvent($config, $configManager));
    }
}
