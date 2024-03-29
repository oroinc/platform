<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\ThemeConfigurationProvider;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider as GeneralThemeConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ThemeConfigurationProviderTest extends TestCase
{
    private GeneralThemeConfigurationProvider|MockObject $generalThemeConfigurationProvider;

    private ThemeConfigurationProvider $provider;

    protected function setUp(): void
    {
        $this->generalThemeConfigurationProvider = $this->createMock(GeneralThemeConfigurationProvider::class);

        $this->provider = new ThemeConfigurationProvider($this->generalThemeConfigurationProvider);
    }

    /**
     * @dataProvider getThemeConfigurationOptionDataProvider
     */
    public function testGetThemeConfigurationOption(?int $scopeIdentifier, mixed $expectedOptionValue): void
    {
        $configurationKey = 'some_option';

        $this->generalThemeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeConfigurationOption')
            ->with($configurationKey, $scopeIdentifier)
            ->willReturn($expectedOptionValue);

        $actualOptionValue = $this->provider->getThemeConfigurationOption($configurationKey, $scopeIdentifier);

        self::assertEquals($expectedOptionValue, $actualOptionValue);
    }

    public function getThemeConfigurationOptionDataProvider(): array
    {
        $noScopeIdentifier = null;
        $idScopeIdentifier = 123;

        return [
            // no scope
            [$noScopeIdentifier, null],
            [$noScopeIdentifier, 'some_option_value'],
            [$noScopeIdentifier, 123],
            [$noScopeIdentifier, 123.321],
            [$noScopeIdentifier, false],
            [$noScopeIdentifier, ['foo' => 'bar']],
            [$noScopeIdentifier, new \stdClass()],
            // scope identifier as id
            [$idScopeIdentifier, null],
            [$idScopeIdentifier, 'some_option_value'],
            [$idScopeIdentifier, 123],
            [$idScopeIdentifier, 123.321],
            [$idScopeIdentifier, false],
            [$idScopeIdentifier, ['foo' => 'bar']],
            [$idScopeIdentifier, new \stdClass()],
        ];
    }
}
