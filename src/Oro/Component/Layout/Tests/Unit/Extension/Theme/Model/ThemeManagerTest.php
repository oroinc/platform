<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactory;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactoryInterface;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class ThemeManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
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

    /**
     * @dataProvider pageTemplatesDataProvider
     *
     * @param string          $childThemeKey
     * @param array           $themesDefinitions
     * @param ArrayCollection $expectedResult
     * @param array           $expectedTitlesResult
     */
    public function testGetThemeMergingPageTemplates(
        $childThemeKey,
        $themesDefinitions,
        $expectedResult,
        $expectedTitlesResult
    ) {
        $manager = new ThemeManager(new ThemeFactory(), $themesDefinitions);
        $theme = $manager->getTheme($childThemeKey);

        $this->assertEquals($expectedResult, $theme->getPageTemplates());
        $this->assertEquals($expectedTitlesResult, $theme->getPageTemplateTitles());
    }

    /**
     * @return array
     */
    public function pageTemplatesDataProvider()
    {
        $childThemeDefinition = $this->getThemeDefinition('Oro Child Theme', 'parent_theme', [
            'templates' => [
                $this->getPageTemplateDefinition('Child Page 1', 'child_1', 'child_route_1'),
            ],
            'titles' => [
                'child_route_1' => 'Child Route 1',
            ],
        ]);

        $parentThemeDefinition = $this->getThemeDefinition('Oro Parent Theme', 'upper_theme', [
            'templates' => [
                $this->getPageTemplateDefinition('Parent Page 1', 'parent_1', 'parent_route_1'),
            ],
            'titles' => [
                'parent_route_1' => 'Parent Route 1',
            ],
        ]);

        $upperThemeDefinition = $this->getThemeDefinition('Oro Upper Theme', null, [
            'templates' => [
                $this->getPageTemplateDefinition('Upper Page 1', 'upper_1', 'upper_route_1'),
                $this->getPageTemplateDefinition('Upper Page 2', 'upper_2', 'upper_route_2'),
            ],
            'titles' => [
                'upper_route_1' => 'Upper Route 1',
                'upper_route_2' => 'Upper Route 2',
            ],
        ]);

        return [
            'is single theme' => [
                'childThemeKey' => 'upper_theme',
                'themesDefinitions' => [
                    'upper_theme' => $upperThemeDefinition,
                ],
                'expectedResult' => new ArrayCollection([
                    'upper_1_upper_route_1' => new PageTemplate('Upper Page 1', 'upper_1', 'upper_route_1'),
                    'upper_2_upper_route_2' => new PageTemplate('Upper Page 2', 'upper_2', 'upper_route_2'),
                ]),
                'expectedTitlesResult' => [
                    'upper_route_1' => 'Upper Route 1',
                    'upper_route_2' => 'Upper Route 2',
                ],
            ],
            'with parent theme' => [
                'childThemeKey' => 'parent_theme',
                'themesDefinitions' => [
                    'parent_theme' => $parentThemeDefinition,
                    'upper_theme' => $upperThemeDefinition,
                ],
                'expectedResult' => new ArrayCollection([
                    'parent_1_parent_route_1' => new PageTemplate('Parent Page 1', 'parent_1', 'parent_route_1'),
                    'upper_1_upper_route_1' => new PageTemplate('Upper Page 1', 'upper_1', 'upper_route_1'),
                    'upper_2_upper_route_2' => new PageTemplate('Upper Page 2', 'upper_2', 'upper_route_2'),
                ]),
                'expectedTitlesResult' => [
                    'parent_route_1' => 'Parent Route 1',
                    'upper_route_1' => 'Upper Route 1',
                    'upper_route_2' => 'Upper Route 2',
                ],
            ],
            'recursive' => [
                'childThemeKey' => 'child_theme',
                'themesDefinitions' => [
                    'child_theme' => $childThemeDefinition,
                    'parent_theme' => $parentThemeDefinition,
                    'upper_theme' => $upperThemeDefinition
                ],
                'expectedResult' => new ArrayCollection([
                    'child_1_child_route_1' => new PageTemplate('Child Page 1', 'child_1', 'child_route_1'),
                    'parent_1_parent_route_1' => new PageTemplate('Parent Page 1', 'parent_1', 'parent_route_1'),
                    'upper_1_upper_route_1' => new PageTemplate('Upper Page 1', 'upper_1', 'upper_route_1'),
                    'upper_2_upper_route_2' => new PageTemplate('Upper Page 2', 'upper_2', 'upper_route_2'),
                ]),
                'expectedTitlesResult' => [
                    'child_route_1' => 'Child Route 1',
                    'parent_route_1' => 'Parent Route 1',
                    'upper_route_1' => 'Upper Route 1',
                    'upper_route_2' => 'Upper Route 2',
                ],
            ]
        ];
    }

    /**
     * @param string $label
     * @param string $key
     * @param string $routeName
     * @return array
     */
    private function getPageTemplateDefinition($label, $key, $routeName)
    {
        return [
            'label' => $label,
            'key' => $key,
            'route_name' => $routeName,
        ];
    }

    /**
     * @param string $label
     * @param string $parent
     * @param array  $pageTemplates
     * @return array
     */
    private function getThemeDefinition($label, $parent, $pageTemplates)
    {
        return [
            'label' => $label,
            'parent' => $parent,
            'config' => [
                'page_templates' => $pageTemplates
            ]
        ];
    }
}
