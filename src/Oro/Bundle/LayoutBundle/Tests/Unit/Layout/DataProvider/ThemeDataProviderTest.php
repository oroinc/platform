<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Model\Theme;
use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\ThemeDataProvider;

class ThemeDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $themeManager;

    /** @var LayoutContext */
    protected $context;

    /** @var ThemeDataProvider */
    protected $dataProvider;

    protected function setUp()
    {
        $this->themeManager = $this->getMockBuilder('Oro\Bundle\LayoutBundle\Theme\ThemeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context      = new LayoutContext();
        $this->dataProvider = new ThemeDataProvider($this->themeManager);
        $this->dataProvider->setContext($this->context);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetIdentifier()
    {
        $this->dataProvider->getIdentifier();
    }

    public function testGetData()
    {
        $themeName = 'test';
        $theme     = new Theme($themeName);

        $this->context['theme'] = $themeName;
        $this->themeManager->expects($this->once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $this->assertSame($theme, $this->dataProvider->getData());
    }
}
