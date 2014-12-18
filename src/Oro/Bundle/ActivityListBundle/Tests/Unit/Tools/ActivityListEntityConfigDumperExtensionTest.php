<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Tools;

use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class ActivityListEntityConfigDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityListEntityConfigDumperExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $listProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $associationBuilder;

    public function setUp()
    {
        $this->listProvider = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
            ->disableOriginalConstructor()->getMock();
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->associationBuilder = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder')
            ->disableOriginalConstructor()->getMock();
        $this->extension = new ActivityListEntityConfigDumperExtension(
            $this->listProvider,
            $this->configManager,
            $this->associationBuilder
        );
    }

    public function testBadActionSupports()
    {
        $this->assertFalse($this->extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE));
    }

    public function testEmptyTargetsSupports()
    {
        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()->getMock();
        $provider
            ->expects($this->once())
            ->method('getConfigs')
            ->willReturn([]);

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->willReturn($provider);

        $this->assertFalse($this->extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE));
    }

    public function testSupports()
    {
        $configId = new EntityConfigId('extend', 'Acme\TestBundle\Entity\TestEntity');
        $config   = new Config($configId);
        $config->set('upgradeable', true);

        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()->getMock();
        $provider
            ->expects($this->once())
            ->method('getConfigs')
            ->willReturn([$config]);

        $this->configManager
            ->expects($this->any())
            ->method('getProvider')
            ->willReturn($provider);

        $this->listProvider
            ->expects($this->once())
            ->method('getTargetEntityClasses')
            ->willReturn(['Acme\TestBundle\Entity\TestEntity']);

        $provider->expects($this->once())
            ->method('hasConfig')
            ->with(ActivityListEntityConfigDumperExtension::ENTITY_CLASS)
            ->willReturn(true);

        $this->assertTrue($this->extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE));
    }

    public function testPreUpdate()
    {
        $configId = new EntityConfigId('extend', 'Acme\TestBundle\Entity\TestEntity');
        $config   = new Config($configId);
        $config->set('upgradeable', true);

        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()->getMock();
        $provider
            ->expects($this->once())
            ->method('getConfigs')
            ->willReturn([$config]);

        $this->listProvider
            ->expects($this->any())
            ->method('getTargetEntityClasses')
            ->willReturn(['Acme\TestBundle\Entity\TestEntity']);

        $this->configManager
            ->expects($this->any())
            ->method('getProvider')
            ->willReturn($provider);

        $this->associationBuilder
            ->expects($this->once())
            ->method('createManyToManyAssociation')
            ->with(
                'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
                'Acme\TestBundle\Entity\TestEntity',
                'activityList'
            );
        $this->extension->preUpdate();
    }
}
