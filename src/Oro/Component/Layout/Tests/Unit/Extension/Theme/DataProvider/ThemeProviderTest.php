<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\DataProvider;

use Oro\Component\Layout\Extension\Theme\DataProvider\ThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class ThemeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
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
                ],
                'styles_new' => [
                    'output' => 'path/to/output/css/new'
                ],
            ],
        ]);

        $this->themeManager->expects($this->once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $this->assertSame('path/to/output/css', $this->provider->getStylesOutput($themeName));
        $this->assertSame('path/to/output/css', $this->provider->getStylesOutput($themeName, 'styles'));
        $this->assertSame('path/to/output/css/new', $this->provider->getStylesOutput($themeName, 'styles_new'));
        $this->assertSame(null, $this->provider->getStylesOutput($themeName, 'undefined section'));
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

    public function testGetStylesOutputWithFallback()
    {
        $grandParentThemeName = 'grand-parent';
        $grandParentTheme = new Theme($grandParentThemeName);
        $grandParentTheme->setConfig([
            'assets' => [
                'styles' => [
                    'output' => 'grand/parent/theme/path/to/output/css'
                ]
            ],
        ]);

        $parentThemeName = 'parent';
        $parentTheme = new Theme($parentThemeName, $grandParentThemeName);

        $themeName = 'theme';
        $theme = new Theme($themeName, $parentThemeName);

        $this->themeManager->expects($this->any())
            ->method('getTheme')
            ->withConsecutive([$themeName], [$parentThemeName], [$grandParentThemeName])
            ->willReturnOnConsecutiveCalls($theme, $parentTheme, $grandParentTheme);

        $this->assertSame('grand/parent/theme/path/to/output/css', $this->provider->getStylesOutput($themeName));
        $this->assertNull($this->provider->getStylesOutput($themeName, 'undefined'));
    }
}
