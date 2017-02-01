<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactoryInterface;

class ThemeManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $factory;

    protected function setUp()
    {
        $this->factory = $this->createMock('Oro\Component\Layout\Extension\Theme\Model\ThemeFactoryInterface');
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

        $themeMock = $this->createMock('Oro\Component\Layout\Extension\Theme\Model\Theme');

        $this->factory->expects($this->once())->method('create')
            ->with($this->equalTo('base'), $this->equalTo(['label' => 'Oro Base theme']))
            ->willReturn($themeMock);

        $this->assertSame($themeMock, $manager->getTheme('base'));
        $this->assertSame($themeMock, $manager->getTheme('base'), 'Should instantiate model once');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The theme name must not be empty.
     */
    public function testGetThemeShouldThrowExceptionIfThemeNameIsEmpty()
    {
        $manager = $this->createManager();
        $manager->getTheme('');
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

        $theme1Mock = $this->createMock('Oro\Component\Layout\Extension\Theme\Model\Theme');
        $theme2Mock = $this->createMock('Oro\Component\Layout\Extension\Theme\Model\Theme');

        $this->factory->expects($this->exactly(2))->method('create')
            ->willReturnOnConsecutiveCalls($theme1Mock, $theme2Mock);

        $this->assertSame(['base' => $theme1Mock, 'oro-black' => $theme2Mock], $manager->getAllThemes());
    }

    public function testGetAllByGroupThemes()
    {
        $manager = $this->createManager(['base' => [], 'oro-black' => []]);

        $theme1Mock = $this->createMock('Oro\Component\Layout\Extension\Theme\Model\Theme');
        $theme1Mock->expects($this->any())->method('getGroups')->willReturn(['base', 'frontend']);
        $theme2Mock = $this->createMock('Oro\Component\Layout\Extension\Theme\Model\Theme');
        $theme2Mock->expects($this->any())->method('getGroups')->willReturn(['frontend']);

        $this->factory->expects($this->exactly(2))->method('create')
            ->willReturnOnConsecutiveCalls($theme1Mock, $theme2Mock);

        $this->assertCount(2, $manager->getAllThemes());
        $this->assertCount(1, $manager->getAllThemes('base'));
        $this->assertCount(2, $manager->getAllThemes('frontend'));
        $this->assertCount(1, $manager->getAllThemes(['base', 'embedded']));
        $this->assertCount(2, $manager->getAllThemes(['base', 'frontend']));
    }

    /**
     * @param array $definitions
     *
     * @return ThemeManager
     */
    protected function createManager(array $definitions = [])
    {
        return new ThemeManager($this->factory, $definitions);
    }

    public function testGetThemeMergingPageTemplates()
    {
        $parentThemeDefinition = [
            'label' => 'Oro Parent theme',
            'config' => []
        ];

        $childThemeDefinition = [
            'label' => 'Oro Child theme',
            'parent' => 'parent_theme',
            'config' => []
        ];
        $manager = $this->createManager(
            [
                'parent_theme' => $parentThemeDefinition,
                'child_theme' => $childThemeDefinition
            ]
        );

        $childTheme = new Theme('Child theme', 'parent_theme');

        $parentTheme = new Theme('Parent theme');
        $parentPageTemplate1 = new PageTemplate('Parent page template label 1', 'page_template_1', 'some_route');
        $parentPageTemplate2 = new PageTemplate('Parent page template label 2', 'page_template_2', 'some_route');
        $parentTheme->addPageTemplate($parentPageTemplate1);
        $parentTheme->addPageTemplate($parentPageTemplate2);
        //Overwrites Parent page template 1
        $childPageTemplate = new PageTemplate('Child Page template 1', 'page_template_1', 'some_route');
        $childTheme->addPageTemplate($childPageTemplate);

        $this->factory
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                ['child_theme', $childThemeDefinition],
                ['parent_theme', $parentThemeDefinition]
            )
            ->willReturnOnConsecutiveCalls($childTheme, $parentTheme);

        $resultAfterMerge = $manager->getTheme('child_theme');

        $this->assertEquals(
            new ArrayCollection([$childPageTemplate, $parentPageTemplate2]),
            $resultAfterMerge->getPageTemplates()
        );
    }
}
