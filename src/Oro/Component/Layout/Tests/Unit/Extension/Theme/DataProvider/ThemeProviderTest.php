<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\DataProvider;

use Oro\Component\Layout\Extension\Theme\DataProvider\ThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class ThemeProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $themeManager;

    /** @var ThemeProvider */
    protected $provider;

    protected function setUp()
    {
        $this->themeManager = $this->getMockBuilder('Oro\Component\Layout\Extension\Theme\Model\ThemeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new ThemeProvider($this->themeManager);
    }

    public function testGetIcon()
    {
        $themeName = 'test';
        $theme     = new Theme($themeName);
        $theme->setIcon('path/to/icon');

        $this->themeManager->expects($this->once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $this->assertSame('path/to/icon', $this->provider->getIcon($themeName));
    }

    public function testGetStylesOutput()
    {
        $themeName = 'test';
        $theme     = new Theme($themeName);
        $theme->setConfig([
            'assets' => [
                'styles' => [
                    'output' => 'path/to/output/css'
                ]
            ]
        ]);

        $this->themeManager->expects($this->once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $this->assertSame('path/to/output/css', $this->provider->getStylesOutput($themeName));
    }

    public function testGetStylesOutputNull()
    {
        $themeName = 'test';
        $theme     = new Theme($themeName);

        $this->themeManager->expects($this->once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $this->assertNull($this->provider->getStylesOutput($themeName));
    }
}
