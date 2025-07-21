<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig;

use Oro\Bundle\LayoutBundle\Twig\ThemeConfigurationTwigExtension;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider as GeneralProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ThemeConfigurationTwigExtensionTest extends TestCase
{
    private GeneralProvider&MockObject $generalProvider;
    private ThemeConfigurationTwigExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->generalProvider = $this->createMock(GeneralProvider::class);

        $this->extension = new ThemeConfigurationTwigExtension($this->generalProvider);
    }

    /**
     * @dataProvider getThemeConfigurationOptionDataProvider
     */
    public function testGetThemeConfigurationOption(mixed $expectedOptionValue): void
    {
        $option = 'some_option';

        $this->generalProvider->expects(self::once())
            ->method('getThemeConfigurationOption')
            ->with($option)
            ->willReturn($expectedOptionValue);

        $actualOptionValue = $this->extension->getThemeConfigurationValue($option);

        self::assertEquals($expectedOptionValue, $actualOptionValue);
    }

    /**
     * @dataProvider getThemeConfigurationOptionDataProvider
     */
    public function testGetThemeDefinitionValue(mixed $expectedValue): void
    {
        $key = 'some_value';

        $this->generalProvider
            ->expects(self::once())
            ->method('getThemeProperty')
            ->with($key)
            ->willReturn($expectedValue);

        $actualValue = $this->extension->getThemeDefinitionValue($key);

        self::assertEquals($expectedValue, $actualValue);
    }

    public function getThemeConfigurationOptionDataProvider(): array
    {
        return [
            [null],
            ['some_option_value'],
            [123],
            [123.321],
            [false],
            [['foo' => 'bar']],
            [new \stdClass()],
        ];
    }
}
