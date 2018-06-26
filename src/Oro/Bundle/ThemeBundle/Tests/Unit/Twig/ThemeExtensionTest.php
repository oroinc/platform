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
    protected $themeRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $theme;

    /** @var ThemeExtension */
    protected $extension;

    protected function setUp()
    {
        $this->themeRegistry = $this->getMockBuilder(ThemeRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->theme = $this->getMockBuilder(Theme::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_theme.registry', $this->themeRegistry)
            ->getContainer($this);

        $this->extension = new ThemeExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(ThemeExtension::NAME, $this->extension->getName());
    }

    public function testGetThemeLogo()
    {
        $this->themeRegistry->expects($this->once())
            ->method('getActiveTheme')
            ->will($this->returnValue($this->theme));

        $logo = 'logo.png';

        $this->theme->expects($this->once())
            ->method('getLogo')
            ->will($this->returnValue($logo));

        $this->assertEquals(
            $logo,
            self::callTwigFunction($this->extension, 'oro_theme_logo', [])
        );
    }

    public function testGetThemeLogoNoActiveTheme()
    {
        $this->themeRegistry->expects($this->once())
            ->method('getActiveTheme')
            ->will($this->returnValue(null));

        $this->assertEquals(
            '',
            self::callTwigFunction($this->extension, 'oro_theme_logo', [])
        );
    }

    public function testGetThemeIcon()
    {
        $this->themeRegistry->expects($this->once())
            ->method('getActiveTheme')
            ->will($this->returnValue($this->theme));

        $icon = 'icon.ico';

        $this->theme->expects($this->once())
            ->method('getIcon')
            ->will($this->returnValue($icon));

        $this->assertEquals(
            $icon,
            self::callTwigFunction($this->extension, 'oro_theme_icon', [])
        );
    }

    public function testGetThemeIconNoActiveTheme()
    {
        $this->themeRegistry->expects($this->once())
            ->method('getActiveTheme')
            ->will($this->returnValue(null));

        $this->assertEquals(
            '',
            self::callTwigFunction($this->extension, 'oro_theme_icon', [])
        );
    }
}
