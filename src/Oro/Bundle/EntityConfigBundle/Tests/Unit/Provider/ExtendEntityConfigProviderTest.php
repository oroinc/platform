<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ExtendEntityConfigProvider;

class ExtendEntityConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExtendEntityConfigProvider */
    private $extendEntityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager */
    private $configManager;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeProvider;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $extendProvider;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extendEntityConfigProvider = new ExtendEntityConfigProvider($this->configManager);

        $this->attributeProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extendProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->withConsecutive(['attribute'], ['extend'])
            ->willReturnOnConsecutiveCalls($this->attributeProvider, $this->extendProvider);
    }

    public function testGetExtendEntityConfigs()
    {
        $configId = new EntityConfigId('extend', 'Class1');
        $extendConfig = new Config($configId, ['is_extend' => true]);
        $notExtendConfig = new Config($configId, ['is_extend' => false]);

        $this->extendProvider->expects($this->once())
            ->method('getConfigs')
            ->with(null, true)
            ->willReturn([$extendConfig, $notExtendConfig]);

        $returnedConfigs = $this->extendEntityConfigProvider->getExtendEntityConfigs();
        $this->assertSame([$extendConfig], $returnedConfigs);
    }

    public function testGetExtendEntityConfigsAttributesOnly()
    {
        $configId = new EntityConfigId('extend', 'Class1');
        $attributeExtendConfig = new Config($configId, ['is_extend' => true, 'has_attributes' => true]);
        $notExtendConfig = new Config($configId, ['is_extend' => false]);

        $this->extendProvider->expects($this->once())
            ->method('getConfigs')
            ->with(null, true)
            ->willReturn([$attributeExtendConfig, $notExtendConfig]);

        $this->attributeProvider->expects($this->once())
            ->method('getConfig')
            ->with('Class1')
            ->willReturn($attributeExtendConfig);

        $returnedConfigs = $this->extendEntityConfigProvider->getExtendEntityConfigs(true);
        $this->assertSame([$attributeExtendConfig], $returnedConfigs);
    }

    public function testGetExtendEntityConfigsAttributesOnlyAndFilter()
    {
        $configId = new EntityConfigId('extend', 'Class1');
        $attributeExtendConfig = new Config($configId, ['is_extend' => true, 'has_attributes' => true]);
        $notAttributeConfig = new Config($configId, ['is_extend' => true, 'has_attributes' => false]);
        $filter = function () {
            //Callable filter
        };

        $this->extendProvider->expects($this->once())
            ->method('filter')
            ->with($filter, null, true)
            ->willReturn([$attributeExtendConfig, $notAttributeConfig]);

        $this->attributeProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->with('Class1')
            ->willReturnOnConsecutiveCalls($attributeExtendConfig, $notAttributeConfig);

        $returnedConfigs = $this->extendEntityConfigProvider->getExtendEntityConfigs(true, $filter);
        $this->assertSame([$attributeExtendConfig], $returnedConfigs);
    }
}
