<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ExtendEntityConfigProvider;

class ExtendEntityConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtendEntityConfigProvider */
    private $extendEntityConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enumProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
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

        $this->enumProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($this->extendProvider);
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

    public function testGetExtendEntityConfigsFilter()
    {
        $configId = new EntityConfigId('extend', 'Class1');
        $extendConfig = new Config($configId, ['is_extend' => true]);
        $notExtendConfig = new Config($configId, ['is_extend' => false]);

        $filter = function () {
            //Callable filter
        };

        $this->extendProvider->expects($this->once())
            ->method('filter')
            ->with($filter, null, true)
            ->willReturn([$extendConfig, $notExtendConfig]);

        $returnedConfigs = $this->extendEntityConfigProvider->getExtendEntityConfigs($filter);
        $this->assertSame([$extendConfig], $returnedConfigs);
    }
}
