<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactory;
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

    /**
     * @dataProvider pageTemplatesDataProvider
     *
     * @param string          $childThemeKey
     * @param array           $themesDefinitions
     * @param ArrayCollection $expectedResult
     */
    public function testGetThemeMergingPageTemplates($childThemeKey, $themesDefinitions, $expectedResult)
    {
        $manager = new ThemeManager(new ThemeFactory(), $themesDefinitions);
        $theme = $manager->getTheme($childThemeKey);

        $this->assertEquals($expectedResult, $theme->getPageTemplates());
    }

    /**
     * @return array
     */
    public function pageTemplatesDataProvider()
    {
        $childThemeDefinition = [
            'label' => 'Oro Child theme',
            'parent' => 'parent_theme',
            'config' => [
                'page_templates' => [
                    'templates' => [
                        [
                            'label' => 'Child Page 1',
                            'key' => 'child_1',
                            'route_name' => 'child_route_1',
                        ],
                    ]
                ]
            ]
        ];

        $parentThemeDefinition = [
            'label' => 'Oro Parent theme',
            'parent' => 'upper_theme',
            'config' => [
                'page_templates' => [
                    'templates' => [
                        [
                            'label' => 'Parent Page 1',
                            'key' => 'parent_1',
                            'route_name' => 'parent_route_1',
                        ],
                    ]
                ]
            ]
        ];

        $upperThemeDefinition = [
            'label' => 'Oro Upper Theme',
            'config' => [
                'page_templates' => [
                    'templates' => [
                        [
                            'label' => 'Upper Page 1',
                            'key' => 'upper_1',
                            'route_name' => 'upper_route_1',
                        ],
                        [
                            'label' => 'Upper Page 2',
                            'key' => 'upper_2',
                            'route_name' => 'upper_route_2',
                        ],
                    ]
                ]
            ]
        ];

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
            ]
        ];
    }
}
