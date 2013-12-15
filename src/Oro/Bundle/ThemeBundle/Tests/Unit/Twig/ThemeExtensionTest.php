<?php


namespace Oro\Bundle\ThemeBundle\Tests\Unit\Twig;

use Oro\Bundle\ThemeBundle\Twig\ThemeExtension;

class ThemeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $themeRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $theme;

    /**
     * @var ThemeExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->themeRegistry = $this->getMockBuilder('Oro\Bundle\ThemeBundle\Model\ThemeRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->theme = $this->getMockBuilder('Oro\Bundle\ThemeBundle\Model\Theme')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ThemeExtension($this->themeRegistry);
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(2, $functions);

        $this->assertInstanceOf('\Twig_SimpleFunction', $functions[0]);
        $this->assertEquals('oro_theme_logo', $functions[0]->getName());
        $this->assertEquals(array($this->extension, 'getThemeLogo'), $functions[0]->getCallable());

        $this->assertInstanceOf('\Twig_SimpleFunction', $functions[1]);
        $this->assertEquals('oro_theme_icon', $functions[1]->getName());
        $this->assertEquals(array($this->extension, 'getThemeIcon'), $functions[1]->getCallable());
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

        $this->assertEquals($logo, $this->extension->getThemeLogo());
    }

    public function testGetThemeLogoNoActiveTheme()
    {
        $this->themeRegistry->expects($this->once())
            ->method('getActiveTheme')
            ->will($this->returnValue(null));

        $this->assertEquals('', $this->extension->getThemeLogo());
    }

    public function testGetThemeIcon()
    {
        $this->themeRegistry->expects($this->once())
            ->method('getActiveTheme')
            ->will($this->returnValue($this->theme));

        $logo = 'icon.ico';

        $this->theme->expects($this->once())
            ->method('getIcon')
            ->will($this->returnValue($logo));

        $this->assertEquals($logo, $this->extension->getThemeIcon());
    }

    public function testGetThemeIconNoActiveTheme()
    {
        $this->themeRegistry->expects($this->once())
            ->method('getActiveTheme')
            ->will($this->returnValue(null));

        $this->assertEquals('', $this->extension->getThemeIcon());
    }
}
