<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Model;

use Oro\Bundle\ThemeBundle\Exception\ThemeNotFoundException;
use Oro\Bundle\ThemeBundle\Model\Theme;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

class ThemeRegistryTest extends \PHPUnit\Framework\TestCase
{
    private const THEME_SETTINGS = [
        'foo' => [
            'label' => 'Foo Theme',
            'icon' => 'favicon.ico',
            'logo' => 'logo.png',
            'screenshot' => 'screenshot.png',
            'rtl_support' => true,
        ],
        'bar' => [
        ]
    ];

    private ThemeRegistry $themeRegistry;

    protected function setUp(): void
    {
        $this->themeRegistry = new ThemeRegistry(self::THEME_SETTINGS);
    }

    public function testGetTheme(): void
    {
        $fooTheme = $this->themeRegistry->getTheme('foo');
        self::assertInstanceOf(Theme::class, $fooTheme);
        self::assertEquals('Foo Theme', $fooTheme->getLabel());
        self::assertEquals('favicon.ico', $fooTheme->getIcon());
        self::assertEquals('logo.png', $fooTheme->getLogo());
        self::assertEquals('screenshot.png', $fooTheme->getScreenshot());
        self::assertTrue($fooTheme->isRtlSupport());
        self::assertSame($fooTheme, $this->themeRegistry->getTheme('foo'));

        $barTheme = $this->themeRegistry->getTheme('bar');
        self::assertInstanceOf(Theme::class, $barTheme);
        self::assertNull($barTheme->getLabel());
        self::assertNull($barTheme->getIcon());
        self::assertNull($barTheme->getLogo());
        self::assertNull($barTheme->getScreenshot());
        self::assertFalse($barTheme->isRtlSupport());
        self::assertSame($barTheme, $this->themeRegistry->getTheme('bar'));

        self::assertEquals(
            ['foo' => $fooTheme, 'bar' => $barTheme],
            $this->themeRegistry->getAllThemes()
        );
    }

    public function testGetThemeNotFoundException(): void
    {
        $this->expectException(ThemeNotFoundException::class);
        $this->expectExceptionMessage('Theme "baz" not found.');

        $this->themeRegistry->getTheme('baz');
    }

    public function testGetActiveTheme(): void
    {
        self::assertNull($this->themeRegistry->getActiveTheme());

        $this->themeRegistry->setActiveTheme('foo');
        $activeTheme = $this->themeRegistry->getActiveTheme();

        self::assertInstanceOf(Theme::class, $activeTheme);
        self::assertEquals('foo', $activeTheme->getName());
    }
}
