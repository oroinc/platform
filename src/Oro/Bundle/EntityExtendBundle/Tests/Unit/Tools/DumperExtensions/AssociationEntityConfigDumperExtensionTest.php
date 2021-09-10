<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AssociationEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class AssociationEntityConfigDumperExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const ASSOCIATION_SCOPE = 'test_scope';
    private const ATTR_NAME = 'enabled';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $associationBuilder;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->associationBuilder = $this->createMock(AssociationBuilder::class);
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
            ->willReturn(self::ASSOCIATION_SCOPE);

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
            ->willReturn(self::ASSOCIATION_SCOPE);

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
            ->willReturn('Test\SourceEntity');
        $extension->expects($this->once())
            ->method('getAssociationScope')
            ->willReturn(self::ASSOCIATION_SCOPE);
        $extension->expects($this->once())
            ->method('getAssociationKind')
            ->willReturn('test');

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
            ->willReturn('Test\SourceEntity');
        $extension->expects($this->once())
            ->method('getAssociationScope')
            ->willReturn(self::ASSOCIATION_SCOPE);
        $extension->expects($this->once())
            ->method('getAssociationKind')
            ->willReturn('test');
        $extension->expects($this->once())
            ->method('getAssociationType')
            ->willReturn('manyToMany');

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
     * @return AssociationEntityConfigDumperExtension|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getExtensionMock(array $methods = [])
    {
        return $this->getMockForAbstractClass(
            AssociationEntityConfigDumperExtension::class,
            [$this->configManager, $this->associationBuilder],
            '',
            true,
            true,
            true,
            $methods
        );
    }

    private function setTargetEntityConfigsExpectations(array $configs = [])
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->once())
            ->method('getConfigs')
            ->willReturn($configs);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with(self::ASSOCIATION_SCOPE)
            ->willReturn($configProvider);
    }
}
