<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader;

use Oro\Bundle\LayoutBundle\Layout\Loader\PathProviderInterface;
use Oro\Bundle\LayoutBundle\Theme\ThemeManager;

abstract class AbstractPathProviderTestCase extends \PHPUnit_Framework_TestCase
{
    /** @var PathProviderInterface */
    protected $provider;

    /** @var ThemeManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $themeManager;

    protected function setUp()
    {
        $this->themeManager = $this->getMockBuilder('\Oro\Bundle\LayoutBundle\Theme\ThemeManager')
            ->disableOriginalConstructor()->getMock();
    }

    protected function tearDown()
    {
        unset($this->provider, $this->themeManager);
    }

    /**
     * @param array $themes
     */
    protected function setUpThemeManager(array $themes)
    {
        $map = [];

        foreach ($themes as $themeName => $theme) {
            $map[] = [$themeName, $theme];
        }

        $this->themeManager->expects($this->any())->method('getTheme')->willReturnMap($map);
    }

    /**
     * @param null|string $parent
     * @param null|string $directory
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getThemeMock($directory = null, $parent = null)
    {
        $theme = $this->getMock('\Oro\Bundle\LayoutBundle\Model\Theme', [], [], '', false);
        $theme->expects($this->any())->method('getParentTheme')->willReturn($parent);
        $theme->expects($this->any())->method('getDirectory')->willReturn($directory);

        return $theme;
    }
}
