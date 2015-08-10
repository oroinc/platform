<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\OrganizationBundle\Event\ConfigSubscriber;

class ConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var ConfigSubscriber */
    protected $subscriber;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

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

    public function testPrePersistEntityConfigForSystemEntityWithNotNoneOwnership()
    {
        $config = new Config(new EntityConfigId('ownership', 'Test\Entity1'));
        $config->set('owner_type', 'USER');

        $extendConfig = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $extendConfig->set('owner', ExtendScope::OWNER_SYSTEM);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue($extendConfig));

        $this->configManager->expects($this->never())
            ->method('persist');
        $this->configManager->expects($this->never())
            ->method('calculateConfigChangeSet');

        $this->subscriber->prePersistEntityConfig(new PersistConfigEvent($config, $this->configManager));
    }

    public function testPrePersistEntityConfigForCustomEntityDoesNotRequireUpdate()
    {
        $config = new Config(new EntityConfigId('ownership', 'Test\Entity1'));
        $config->set('owner_type', 'USER');
        $config->set('owner_field_name', 'owner');
        $config->set('owner_column_name', 'owner_id');
        $config->set('organization_field_name', 'organization');
        $config->set('organization_column_name', 'organization_id');

        $extendConfig = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue($extendConfig));

        $this->configManager->expects($this->never())
            ->method('persist');
        $this->configManager->expects($this->never())
            ->method('calculateConfigChangeSet');

        $this->subscriber->prePersistEntityConfig(new PersistConfigEvent($config, $this->configManager));
    }

    public function testPrePersistEntityConfigForCustomEntityWithNotNoneOwnership()
    {
        $config = new Config(new EntityConfigId('ownership', 'Test\Entity1'));
        $config->set('owner_type', 'USER');

        $extendConfig = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1')
            ->will($this->returnValue($extendConfig));

        $expectedConfig = clone $config;
        $expectedConfig->set('owner_field_name', 'owner');
        $expectedConfig->set('owner_column_name', 'owner_id');
        $expectedConfig->set('organization_field_name', 'organization');
        $expectedConfig->set('organization_column_name', 'organization_id');

        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($expectedConfig);
        $this->configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($expectedConfig);

        $this->subscriber->prePersistEntityConfig(new PersistConfigEvent($config, $this->configManager));
    }

    public function testPrePersistEntityConfigWithNoneOwnership()
    {
        $config = new Config(new EntityConfigId('ownership', 'Test\Entity1'));
        $config->set('owner_type', 'NONE');

        $expectedConfig = new Config(new EntityConfigId('ownership', 'Test\Entity1'));

        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($expectedConfig);
        $this->configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($expectedConfig);

        $this->subscriber->prePersistEntityConfig(new PersistConfigEvent($config, $this->configManager));
    }

    public function testPrePersistEntityConfigNotOwnershipScope()
    {
        $config = new Config(new EntityConfigId('test', 'Test\Entity1'));

        $this->configManager->expects($this->never())
            ->method('persist');

        $this->subscriber->prePersistEntityConfig(new PersistConfigEvent($config, $this->configManager));
    }
}
