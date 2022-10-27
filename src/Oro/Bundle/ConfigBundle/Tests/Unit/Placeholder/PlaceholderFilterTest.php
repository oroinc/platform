<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Placeholder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var PlaceholderFilter */
    private $filter;

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
