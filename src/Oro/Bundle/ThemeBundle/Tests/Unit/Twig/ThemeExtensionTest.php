<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Twig;

use Oro\Bundle\ThemeBundle\Model\Theme;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;
use Oro\Bundle\ThemeBundle\Twig\ThemeExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ThemeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private ThemeRegistry|\PHPUnit\Framework\MockObject\MockObject $themeRegistry;

    private Theme|\PHPUnit\Framework\MockObject\MockObject $theme;

    private ThemeExtension $extension;

    protected function setUp(): void
    {
        $this->themeRegistry = $this->createMock(ThemeRegistry::class);
        $this->theme = $this->createMock(Theme::class);

        $container = self::getContainerBuilder()
            ->add(ThemeRegistry::class, $this->themeRegistry)
            ->getContainer($this);

        $this->extension = new ThemeExtension($container);
    }

    public function testGetThemeLogo(): void
    {
        $this->themeRegistry->expects(self::once())
            ->method('getActiveTheme')
            ->willReturn($this->theme);

        $logo = 'logo.png';

        $this->theme->expects(self::once())
            ->method('getLogo')
            ->willReturn($logo);

        self::assertEquals(
            $logo,
            self::callTwigFunction($this->extension, 'oro_theme_logo', [])
        );
    }

    public function testGetThemeLogoNoActiveTheme(): void
    {
        $this->themeRegistry->expects(self::once())
            ->method('getActiveTheme')
            ->willReturn(null);

        self::assertEquals(
            '',
            self::callTwigFunction($this->extension, 'oro_theme_logo', [])
        );
    }

    public function testGetThemeIcon(): void
    {
        $this->themeRegistry->expects(self::once())
            ->method('getActiveTheme')
            ->willReturn($this->theme);

        $icon = 'icon.ico';

        $this->theme->expects(self::once())
            ->method('getIcon')
            ->willReturn($icon);

        self::assertEquals(
            $icon,
            self::callTwigFunction($this->extension, 'oro_theme_icon', [])
        );
    }

    public function testGetThemeIconNoActiveTheme(): void
    {
        $this->themeRegistry->expects(self::once())
            ->method('getActiveTheme')
            ->willReturn(null);

        self::assertEquals(
            '',
            self::callTwigFunction($this->extension, 'oro_theme_icon', [])
        );
    }
}
