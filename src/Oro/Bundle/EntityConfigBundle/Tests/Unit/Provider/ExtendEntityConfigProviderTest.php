<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ExtendEntityConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExtendEntityConfigProviderTest extends TestCase
{
    private ExtendEntityConfigProvider $extendEntityConfigProvider;
    private ConfigManager&MockObject $configManager;
    private ConfigProvider&MockObject $attributeProvider;
    private ConfigProvider&MockObject $enumProvider;
    private ConfigProvider&MockObject $extendProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->extendEntityConfigProvider = new ExtendEntityConfigProvider($this->configManager);
        $this->attributeProvider = $this->createMock(ConfigProvider::class);
        $this->extendProvider = $this->createMock(ConfigProvider::class);
        $this->enumProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($this->extendProvider);
    }

    public function testGetExtendEntityConfigs(): void
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

    public function testGetExtendEntityConfigsFilter(): void
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
