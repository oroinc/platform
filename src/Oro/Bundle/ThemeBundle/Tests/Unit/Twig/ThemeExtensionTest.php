<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Twig;

use Oro\Bundle\ThemeBundle\Model\Theme;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;
use Oro\Bundle\ThemeBundle\Twig\ThemeExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ThemeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $themeRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $theme;

    /** @var ThemeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->themeRegistry = $this->createMock(ThemeRegistry::class);
        $this->theme = $this->createMock(Theme::class);

        $container = self::getContainerBuilder()
            ->add(ThemeRegistry::class, $this->themeRegistry)
            ->getContainer($this);

        $this->extension = new ThemeExtension($container);
    }

    public function testGetThemeLogo()
    {
        $this->themeRegistry->expects($this->once())
            ->method('getActiveTheme')
            ->willReturn($this->theme);

        $logo = 'logo.png';

        $this->theme->expects($this->once())
            ->method('getLogo')
            ->willReturn($logo);

        $this->assertEquals(
            $logo,
            self::callTwigFunction($this->extension, 'oro_theme_logo', [])
        );
    }

    public function testGetThemeLogoNoActiveTheme()
    {
        $this->themeRegistry->expects($this->once())
            ->method('getActiveTheme')
            ->willReturn(null);

        $this->assertEquals(
            '',
            self::callTwigFunction($this->extension, 'oro_theme_logo', [])
        );
    }

    public function testGetThemeIcon()
    {
        $this->themeRegistry->expects($this->once())
            ->method('getActiveTheme')
            ->willReturn($this->theme);

        $icon = 'icon.ico';

        $this->theme->expects($this->once())
            ->method('getIcon')
            ->willReturn($icon);

        $this->assertEquals(
            $icon,
            self::callTwigFunction($this->extension, 'oro_theme_icon', [])
        );
    }

    public function testGetThemeIconNoActiveTheme()
    {
        $this->themeRegistry->expects($this->once())
            ->method('getActiveTheme')
            ->willReturn(null);

        $this->assertEquals(
            '',
            self::callTwigFunction($this->extension, 'oro_theme_icon', [])
        );
    }
}
