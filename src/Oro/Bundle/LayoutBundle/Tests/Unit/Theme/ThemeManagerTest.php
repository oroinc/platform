<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Theme;

use Oro\Bundle\LayoutBundle\Theme\ThemeManager;
use Oro\Bundle\LayoutBundle\Theme\ThemeFactoryInterface;

class ThemeManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $factory;

    protected function setUp()
    {
        $this->factory = $this->getMock('Oro\Bundle\LayoutBundle\Theme\ThemeFactoryInterface');
    }

    protected function tearDown()
    {
        unset($this->factory);
    }

    public function testManagerWorkWithoutKnownThemes()
    {
        $manager = $this->createManager();

        $this->assertEmpty($manager->getThemeNames());
        $this->assertEmpty($manager->getAllThemes());

        $this->assertInternalType('array', $manager->getThemeNames());
        $this->assertInternalType('array', $manager->getAllThemes());

        $this->assertFalse($manager->hasTheme('unknown'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unable to retrieve definition for theme "unknown"
     */
    public function testTryingToGetUnknownThemeModel()
    {
        $manager = $this->createManager();

        $manager->getTheme('unknown');
    }

    public function testGetThemeObject()
    {
        $manager = $this->createManager(['base' => ['label' => 'Oro Base theme']]);

        $themeMock = $this->getMock('Oro\Bundle\LayoutBundle\Model\Theme', [], [], '', false);

        $this->factory->expects($this->once())->method('create')
            ->with($this->equalTo('base'), $this->equalTo(['label' => 'Oro Base theme']))
            ->willReturn($themeMock);

        $this->assertSame($themeMock, $manager->getTheme('base'));
        $this->assertSame($themeMock, $manager->getTheme('base'), 'Should instantiate model once');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Impossible to retrieve active theme due to miss configuration
     */
    public function testTryingToGetActiveThemeModelWhenNotConfigured()
    {
        $manager = $this->createManager();
        $manager->getTheme();
    }

    public function testActiveThemePassedThroughConstructor()
    {
        $manager = $this->createManager(['base' => []], 'base');

        $themeMock = $this->getMock('Oro\Bundle\LayoutBundle\Model\Theme', [], [], '', false);

        $this->factory->expects($this->once())->method('create')
            ->with($this->equalTo('base'))->willReturn($themeMock);

        $this->assertSame($themeMock, $manager->getTheme());
    }

    public function testActiveThemePassedThroughSetter()
    {
        $manager = $this->createManager(['base' => []]);
        $manager->setActiveTheme('base');

        $themeMock = $this->getMock('Oro\Bundle\LayoutBundle\Model\Theme', [], [], '', false);

        $this->factory->expects($this->once())->method('create')
            ->with($this->equalTo('base'))->willReturn($themeMock);

        $this->assertSame($themeMock, $manager->getTheme());
    }

    public function testGetThemeNames()
    {
        $manager = $this->createManager(['base' => [], 'oro-black' => []]);

        $this->assertSame(['base', 'oro-black'], $manager->getThemeNames());
    }

    public function testHasTheme()
    {
        $manager = $this->createManager(['base' => [], 'oro-black' => []]);

        $this->assertTrue($manager->hasTheme('base'), 'Has base theme');
        $this->assertTrue($manager->hasTheme('oro-black'), 'Has black theme');
        $this->assertFalse($manager->hasTheme('unknown'), 'Does not have unknown theme');
    }

    public function testGetAllThemes()
    {
        $manager = $this->createManager(['base' => [], 'oro-black' => []]);

        $theme1Mock = $this->getMock('Oro\Bundle\LayoutBundle\Model\Theme', [], [], '', false);
        $theme2Mock = $this->getMock('Oro\Bundle\LayoutBundle\Model\Theme', [], [], '', false);

        $this->factory->expects($this->exactly(2))->method('create')
            ->willReturnOnConsecutiveCalls($theme1Mock, $theme2Mock);

        $this->assertSame(['base' => $theme1Mock, 'oro-black' => $theme2Mock], $manager->getAllThemes());
    }

    /**
     * @param array       $definitions
     * @param string|null $activeTheme
     *
     * @return ThemeManager
     */
    protected function createManager(array $definitions = [], $activeTheme = null)
    {
        return new ThemeManager($this->factory, $definitions, $activeTheme);
    }
}
