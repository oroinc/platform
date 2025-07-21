<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Layout\DataProvider\SystemConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SystemConfigProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private SystemConfigProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new SystemConfigProvider($this->configManager);
    }

    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue(array $arguments): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->withConsecutive($arguments);

        call_user_func_array([$this->provider, 'getValue'], $arguments);
    }

    public function getValueDataProvider(): array
    {
        return [
            'with config parameter only' => [['oro_config.test']],
            'with config, default parameters' => [['oro_config.test', true]],
            'with config, default, full parameters' => [['oro_config.test', true, true]],
            'with config, default, full, scope identifier parameters' => [['oro_config.test', true, true, 1234]],
        ];
    }
}
