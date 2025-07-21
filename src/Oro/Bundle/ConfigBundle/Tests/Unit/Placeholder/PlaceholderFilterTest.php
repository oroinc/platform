<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Placeholder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Placeholder\PlaceholderFilter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaceholderFilterTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private PlaceholderFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->filter = new PlaceholderFilter($this->configManager);
    }

    /**
     * @dataProvider isEnabledDataProvider
     */
    public function testIsEnabled($configValue, bool $expectedResult): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('test_config_option')
            ->willReturn($configValue);

        self::assertSame($expectedResult, $this->filter->isEnabled('test_config_option'));
    }

    public function isEnabledDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
            [1, true],
            [0, false],
            [null, false]
        ];
    }
}
