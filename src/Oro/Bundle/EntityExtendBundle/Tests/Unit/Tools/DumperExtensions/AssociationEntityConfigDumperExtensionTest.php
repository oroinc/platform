<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AssociationEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class AssociationEntityConfigDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ASSOCIATION_SCOPE = 'test_scope';
    const ATTR_NAME         = 'enabled';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $associationBuilder;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->associationBuilder = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSupportsPostUpdate()
    {
        $extension = $this->getExtensionMock();

        $this->configManager->expects($this->never())
            ->method('getProvider');

        $this->assertFalse(
            $extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE)
        );
    }

    public function testSupportsPreUpdate()
    {
        $extension = $this->getExtensionMock(['getAssociationScope']);

        $extension->expects($this->once())
            ->method('getAssociationScope')
            ->will($this->returnValue(self::ASSOCIATION_SCOPE));

        $config1 = new Config(new EntityConfigId(self::ASSOCIATION_SCOPE, 'Test\Entity1'));
        $config1->set(self::ATTR_NAME, true);
        $config2 = new Config(new EntityConfigId(self::ASSOCIATION_SCOPE, 'Test\Entity2'));

        $this->setTargetEntityConfigsExpectations([$config1, $config2]);

        $this->assertTrue(
            $extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE)
        );
    }

    public function testSupportsPreUpdateNoApplicableTargetEntities()
    {
        $extension = $this->getExtensionMock(['getAssociationScope']);

        $extension->expects($this->once())
            ->method('getAssociationScope')
            ->will($this->returnValue(self::ASSOCIATION_SCOPE));

        $config1 = new Config(new EntityConfigId(self::ASSOCIATION_SCOPE, 'Test\Entity1'));

        $this->setTargetEntityConfigsExpectations([$config1]);

        $this->assertFalse(
            $extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE)
        );
    }

    public function testPreUpdate()
    {
        $extension = $this->getExtensionMock(
            ['getAssociationEntityClass', 'getAssociationScope', 'getAssociationKind']
        );

        $extension->expects($this->once())
            ->method('getAssociationEntityClass')
            ->will($this->returnValue('Test\SourceEntity'));
        $extension->expects($this->once())
            ->method('getAssociationScope')
            ->will($this->returnValue(self::ASSOCIATION_SCOPE));
        $extension->expects($this->once())
            ->method('getAssociationKind')
            ->will($this->returnValue('test'));

        $config1 = new Config(new EntityConfigId(self::ASSOCIATION_SCOPE, 'Test\Entity1'));
        $config1->set(self::ATTR_NAME, true);
        $config2 = new Config(new EntityConfigId(self::ASSOCIATION_SCOPE, 'Test\Entity2'));

        $this->setTargetEntityConfigsExpectations([$config1, $config2]);

        $this->associationBuilder->expects($this->once())
            ->method('createManyToOneAssociation')
            ->with('Test\SourceEntity', 'Test\Entity1', 'test');

        $extension->preUpdate();
    }

    public function testPreUpdateForManyToMany()
    {
        $extension = $this->getExtensionMock(
            ['getAssociationEntityClass', 'getAssociationScope', 'getAssociationKind', 'getAssociationType']
        );

        $extension->expects($this->once())
            ->method('getAssociationEntityClass')
            ->will($this->returnValue('Test\SourceEntity'));
        $extension->expects($this->once())
            ->method('getAssociationScope')
            ->will($this->returnValue(self::ASSOCIATION_SCOPE));
        $extension->expects($this->once())
            ->method('getAssociationKind')
            ->will($this->returnValue('test'));
        $extension->expects($this->once())
            ->method('getAssociationType')
            ->will($this->returnValue('manyToMany'));

        $config1 = new Config(new EntityConfigId(self::ASSOCIATION_SCOPE, 'Test\Entity1'));
        $config1->set(self::ATTR_NAME, true);
        $config2 = new Config(new EntityConfigId(self::ASSOCIATION_SCOPE, 'Test\Entity2'));

        $this->setTargetEntityConfigsExpectations([$config1, $config2]);

        $this->associationBuilder->expects($this->once())
            ->method('createManyToManyAssociation')
            ->with('Test\SourceEntity', 'Test\Entity1', 'test');

        $extension->preUpdate();
    }

    /**
     * @param string[] $methods
     *
     * @return AssociationEntityConfigDumperExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getExtensionMock(array $methods = [])
    {
        return $this->getMockForAbstractClass(
            'Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AssociationEntityConfigDumperExtension',
            [$this->configManager, $this->associationBuilder],
            '',
            true,
            true,
            true,
            $methods
        );
    }

    protected function setTargetEntityConfigsExpectations(array $configs = [])
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue($configs));
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with(self::ASSOCIATION_SCOPE)
            ->will($this->returnValue($configProvider));
    }
}
