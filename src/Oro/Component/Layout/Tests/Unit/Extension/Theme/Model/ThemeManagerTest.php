<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CacheBundle\Adapter\ChainAdapter;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Component\Layout\Extension\Theme\Event\ThemeConfigOptionGetEvent;
use Oro\Component\Layout\Extension\Theme\Event\ThemeOptionGetEvent;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeDefinitionBagInterface;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactory;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactoryInterface;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
final class ThemeManagerTest extends TestCase
{
    private ThemeFactoryInterface&MockObject $factory;
    private ThemeDefinitionBagInterface&MockObject $themeDefinitionBag;
    private EventDispatcherInterface&MockObject $dispatcher;
    private ChainAdapter&MockObject $cache;
    private PropertyAccessorInterface $propertyAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = $this->createMock(ThemeFactoryInterface::class);
        $this->themeDefinitionBag = $this->createMock(ThemeDefinitionBagInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->cache = $this->createMock(ChainAdapter::class);
        $this->propertyAccessor = new PropertyAccessor();
    }

    private function createManager(
        array $definitions = [],
        ?ThemeFactoryInterface $factory = null,
        array $enabledThemes = [],
        array $inheritedThemeOptions = []
    ): ThemeManager {
        $this->themeDefinitionBag->expects(self::any())
            ->method('getThemeNames')
            ->willReturn(array_keys($definitions));
        $this->themeDefinitionBag->expects(self::any())
            ->method('getThemeDefinition')
            ->willReturnCallback(function ($themeName) use ($definitions) {
                return $definitions[$themeName] ?? null;
            });

        return new ThemeManager(
            $factory ?? $this->factory,
            $this->themeDefinitionBag,
            $this->dispatcher,
            $this->cache,
            $this->propertyAccessor,
            $enabledThemes,
            $inheritedThemeOptions
        );
    }

    public function testManagerWorkWithoutKnownThemes(): void
    {
        $manager = $this->createManager();

        self::assertEmpty($manager->getThemeNames());
        self::assertEmpty($manager->getAllThemes());

        self::assertIsArray($manager->getThemeNames());
        self::assertIsArray($manager->getAllThemes());

        self::assertFalse($manager->hasTheme('unknown'));
    }

    public function testTryingToGetUnknownThemeModel(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to retrieve definition for theme "unknown"');

        $manager = $this->createManager();

        $manager->getTheme('unknown');
    }

    public function testGenOnlyEnabledThemes(): void
    {
        $this->factory->expects(self::any())
            ->method('create')
            ->willReturn($this->createMock(Theme::class));

        $manager = $this->createManager(
            [
                'base' => [],
                'default' => [],
                'custom' => [],
            ],
            null,
            ['default', 'base']
        );

        self::assertCount(2, $manager->getEnabledThemes());
    }

    public function testEmptyEnabledThemes(): void
    {
        $this->factory->expects(self::any())
            ->method('create')
            ->willReturn($this->createMock(Theme::class));

        $manager = $this->createManager(
            [
                'base' => [],
                'default' => [],
                'custom' => [],
            ]
        );

        self::assertCount(3, $manager->getEnabledThemes());
    }

    public function testEnabledThemesWithWrongNames(): void
    {
        $this->factory->expects(self::any())
            ->method('create')
            ->willReturn($this->createMock(Theme::class));

        $manager = $this->createManager(
            [
                'base' => [],
                'default' => [],
                'custom' => [],
            ],
            null,
            ['theme1', 'theme2']
        );

        self::assertCount(3, $manager->getEnabledThemes());
    }

    public function testGetThemeObject(): void
    {
        $manager = $this->createManager(['base' => ['label' => 'Oro Base theme']]);

        $themeMock = $this->createMock(Theme::class);

        $this->factory->expects(self::once())
            ->method('create')
            ->with('base', ['label' => 'Oro Base theme'])
            ->willReturn($themeMock);

        self::assertSame($themeMock, $manager->getTheme('base'));
        self::assertSame($themeMock, $manager->getTheme('base'), 'Should instantiate model once');
    }

    public function testGetThemeShouldThrowExceptionIfThemeNameIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The theme name must not be empty.');

        $manager = $this->createManager();
        $manager->getTheme('');
    }

    public function testGetThemeNames(): void
    {
        $manager = $this->createManager(['base' => [], 'oro-black' => []]);

        self::assertSame(['base', 'oro-black'], $manager->getThemeNames());
    }

    public function testHasTheme(): void
    {
        $manager = $this->createManager(['base' => [], 'oro-black' => []]);

        self::assertTrue($manager->hasTheme('base'), 'Has base theme');
        self::assertTrue($manager->hasTheme('oro-black'), 'Has black theme');
        self::assertFalse($manager->hasTheme('unknown'), 'Does not have unknown theme');
    }

    public function testGetAllThemes(): void
    {
        $manager = $this->createManager(['base' => [], 'oro-black' => []]);

        $theme1Mock = $this->createMock(Theme::class);
        $theme2Mock = $this->createMock(Theme::class);

        $this->factory->expects(self::exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($theme1Mock, $theme2Mock);

        self::assertSame(['base' => $theme1Mock, 'oro-black' => $theme2Mock], $manager->getAllThemes());
    }

    public function testGetAllByGroupThemes(): void
    {
        $manager = $this->createManager(['base' => [], 'oro-black' => []]);

        $theme1Mock = $this->createMock(Theme::class);
        $theme1Mock->expects(self::any())
            ->method('getGroups')
            ->willReturn(['base', 'frontend']);
        $theme2Mock = $this->createMock(Theme::class);
        $theme2Mock->expects(self::any())
            ->method('getGroups')
            ->willReturn(['frontend']);

        $this->factory->expects(self::exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($theme1Mock, $theme2Mock);

        self::assertCount(2, $manager->getAllThemes());
        self::assertCount(1, $manager->getAllThemes('base'));
        self::assertCount(2, $manager->getAllThemes('frontend'));
        self::assertCount(1, $manager->getAllThemes(['base', 'embedded']));
        self::assertCount(2, $manager->getAllThemes(['base', 'frontend']));
    }

    /**
     * @dataProvider pageTemplatesDataProvider
     */
    public function testGetThemeMergingPageTemplates(
        string $childThemeKey,
        array $themesDefinitions,
        ArrayCollection $expectedResult,
        array $expectedTitlesResult
    ): void {
        $manager = $this->createManager(
            $themesDefinitions,
            new ThemeFactory(PropertyAccess::createPropertyAccessor())
        );
        $theme = $manager->getTheme($childThemeKey);

        self::assertEquals($expectedResult, $theme->getPageTemplates());
        self::assertEquals($expectedTitlesResult, $theme->getPageTemplateTitles());
    }

    public function pageTemplatesDataProvider(): array
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
                    'upper_theme' => $upperThemeDefinition,
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
            ],
        ];
    }

    private function getPageTemplateDefinition(string $label, string $key, string $routeName): array
    {
        return [
            'label' => $label,
            'key' => $key,
            'route_name' => $routeName,
        ];
    }

    private function getThemeDefinition(string $label, ?string $parent, array $pageTemplates): array
    {
        return [
            'label' => $label,
            'parent' => $parent,
            'config' => [
                'page_templates' => $pageTemplates,
            ],
        ];
    }

    public function testGetThemesHierarchyWhenNoTheme(): void
    {
        $themeName = 'sample_theme';
        $this->expectExceptionObject(
            new \LogicException(sprintf('Unable to retrieve definition for theme "%s".', $themeName))
        );

        $this->createManager()->getThemesHierarchy($themeName);
    }

    public function testGetThemesHierarchyWhenNoParentTheme(): void
    {
        $themeName = 'sample_theme';
        $theme = new Theme($themeName);

        $this->factory->expects(self::once())
            ->method('create')
            ->willReturn($theme);

        $themeManager = $this->createManager([$themeName => []]);
        self::assertEquals([$theme], $themeManager->getThemesHierarchy($themeName));
    }

    public function testGetThemesHierarchyWhenHasParentTheme(): void
    {
        $themeName = 'sample_theme';
        $parentName = 'parent_theme';
        $theme = new Theme($themeName, $parentName);
        $parentTheme = new Theme($parentName);

        $this->factory->expects(self::exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($theme, $parentTheme);

        $themeManager = $this->createManager([$themeName => [], $parentName => []]);
        self::assertEquals([$parentTheme, $theme], $themeManager->getThemesHierarchy($themeName));
    }

    /**
     * @dataProvider themeHasParentDataProvider
     */
    public function testThemeWithoutParentReturnsFalse(
        bool $hasParent,
        string $themeToCheck,
        array $parentThemesToCheck
    ): void {
        $base = $this->createMock(Theme::class);
        $base->expects(self::any())
            ->method('getName')
            ->willReturn('base');
        $base->expects(self::any())
            ->method('getPageTemplates')
            ->willReturn([]);
        $base->expects(self::any())
            ->method('getPageTemplateTitles')
            ->willReturn([]);

        $theme1 = $this->createMock(Theme::class);
        $theme1->expects(self::any())
            ->method('getName')
            ->willReturn('theme1');
        $theme1->expects(self::any())
            ->method('getParentTheme')
            ->willReturn('base');

        $theme2 = $this->createMock(Theme::class);
        $theme2->expects(self::any())
            ->method('getName')
            ->willReturn('theme2');

        $this->factory->expects(self::any())
            ->method('create')
            ->willReturnCallback(function ($name) use ($base, $theme1, $theme2) {
                switch ($name) {
                    case 'base':
                        return $base;
                    case 'theme1':
                        return $theme1;
                    case 'theme2':
                        return $theme2;
                }
            });

        $manager = $this->createManager(
            [
                'base' => [],
                'default' => [],
                'custom' => [],
                'theme1' => ['parent' => 'base'],
                'theme2' => [],

            ],
            null,
            ['theme1', 'theme2']
        );

        $this->assertEquals($hasParent, $manager->themeHasParent($themeToCheck, $parentThemesToCheck));
    }

    public function themeHasParentDataProvider(): array
    {
        return [
            'theme without parent' => [
                'hasParent' => false,
                'themeToCheck' => 'theme2',
                'parentThemesToCheck' => ['base'],
            ],
            'theme with parent' => [
                'hasParent' => true,
                'themeToCheck' => 'theme1',
                'parentThemesToCheck' => ['base'],
            ],
        ];
    }

    public function testGetThemeConfigOptionWithCache(): void
    {
        $manager = $this->createManager();

        $cacheItem = (new CacheItem())->set(['icon']);
        ReflectionUtil::setPropertyValue($cacheItem, 'isHit', true);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('oro_theme.config_optiondefaulticons1')
            ->willReturn($cacheItem);

        self::assertSame(['icon'], $manager->getThemeConfigOption('default', 'icons'));
    }

    public function testGetThemeNoInheritedConfigOption(): void
    {
        $theme = (new Theme('default'))->setConfigByKey('icons', ['icon']);
        $this->factory->expects(self::any())
            ->method('create')
            ->willReturn($theme);

        $manager = $this->createManager(['default' => []], null, ['default']);

        $cacheItem = new CacheItem();
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('oro_theme.config_optiondefaulticons1')
            ->willReturn($cacheItem);

        $event = new ThemeConfigOptionGetEvent($manager, 'default', 'icons', true, ['icon']);
        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$event, ThemeConfigOptionGetEvent::NAME],
                [$event, \sprintf('%s.%s', ThemeConfigOptionGetEvent::NAME, 'icons')]
            );

        $this->cache->expects(self::once())
            ->method('save')
            ->with($cacheItem->set(['icon']));

        self::assertSame(['icon'], $manager->getThemeConfigOption('default', 'icons'));
    }

    public function testGetThemeInheritedConfigOption(): void
    {
        $definitions = ['default' => [], 'base' => []];
        $parentTheme = (new Theme('base'))->setConfigByKey('icons', ['icon2']);
        $theme = (new Theme('default', 'base'))->setConfigByKey('icons', ['icon']);

        $this->themeDefinitionBag->expects(self::any())
            ->method('getThemeDefinition')
            ->willReturn([]);

        $this->factory->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive(['default', []], ['base', []])
            ->willReturnOnConsecutiveCalls($theme, $parentTheme);

        $manager = $this->createManager($definitions, null, ['default', 'base'], ['config.icons']);

        $cacheItem = new CacheItem();
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('oro_theme.config_optiondefaulticons1')
            ->willReturn($cacheItem);

        $event = new ThemeConfigOptionGetEvent($manager, 'default', 'icons', true, ['icon']);
        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$event, ThemeConfigOptionGetEvent::NAME],
                [$event, \sprintf('%s.%s', ThemeConfigOptionGetEvent::NAME, 'icons')]
            );

        $this->cache->expects(self::once())
            ->method('save')
            ->with($cacheItem->set(['icon']));

        self::assertSame(['icon'], $manager->getThemeConfigOption('default', 'icons'));
    }

    public function testGetThemeNoConfigOption(): void
    {
        $this->factory->expects(self::any())
            ->method('create')
            ->willReturn(new Theme('default'));

        $manager = $this->createManager(['default' => []], null, ['default']);

        $cacheItem = new CacheItem();
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('oro_theme.config_optiondefaulticons1')
            ->willReturn($cacheItem);

        $event = new ThemeConfigOptionGetEvent($manager, 'default', 'icons', true, null);
        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$event, ThemeConfigOptionGetEvent::NAME],
                [$event, \sprintf('%s.%s', ThemeConfigOptionGetEvent::NAME, 'icons')]
            );

        $this->cache->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        self::assertNull($manager->getThemeConfigOption('default', 'icons'));
    }

    public function testGetThemeOptionWithCache(): void
    {
        $manager = $this->createManager();

        $cacheItem = (new CacheItem())->set(['font']);
        ReflectionUtil::setPropertyValue($cacheItem, 'isHit', true);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('oro_theme.optiondefaultfonts1')
            ->willReturn($cacheItem);

        self::assertSame(['font'], $manager->getThemeOption('default', 'fonts'));
    }

    public function testGetThemeNoInheritedOption(): void
    {
        $theme = (new Theme('default'))->setFonts(['font']);
        $this->factory->expects(self::any())
            ->method('create')
            ->willReturn($theme);

        $manager = $this->createManager(['default' => []], null, ['default']);

        $cacheItem = new CacheItem();
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('oro_theme.optiondefaultfonts1')
            ->willReturn($cacheItem);

        $event = new ThemeOptionGetEvent($manager, 'default', 'fonts', true, ['font']);
        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$event, ThemeOptionGetEvent::NAME],
                [$event, \sprintf('%s.%s', ThemeOptionGetEvent::NAME, 'fonts')]
            );

        $this->cache->expects(self::once())
            ->method('save')
            ->with($cacheItem->set(['font']));

        self::assertSame(['font'], $manager->getThemeOption('default', 'fonts'));
    }

    public function testGetThemeInheritedOption(): void
    {
        $definitions = ['default' => [], 'base' => []];
        $parentTheme = (new Theme('base'))->setFonts(['font2']);
        $theme = (new Theme('default', 'base'))->setFonts(['font']);

        $this->themeDefinitionBag->expects(self::any())
            ->method('getThemeDefinition')
            ->willReturn([]);

        $this->factory->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive(['default', []], ['base', []])
            ->willReturnOnConsecutiveCalls($theme, $parentTheme);

        $manager = $this->createManager($definitions, null, ['default', 'base'], ['fonts']);

        $cacheItem = new CacheItem();
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('oro_theme.optiondefaultfonts1')
            ->willReturn($cacheItem);

        $event = new ThemeOptionGetEvent($manager, 'default', 'fonts', true, ['font']);
        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$event, ThemeOptionGetEvent::NAME],
                [$event, \sprintf('%s.%s', ThemeOptionGetEvent::NAME, 'fonts')]
            );

        $this->cache->expects(self::once())
            ->method('save')
            ->with($cacheItem->set(['font']));

        self::assertSame(['font'], $manager->getThemeOption('default', 'fonts'));
    }

    public function testGetThemeNoOption(): void
    {
        $this->factory->expects(self::any())
            ->method('create')
            ->willReturn(new Theme('default'));

        $manager = $this->createManager(['default' => []], null, ['default']);

        $cacheItem = new CacheItem();
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('oro_theme.optiondefaultfonts1')
            ->willReturn($cacheItem);

        $event = new ThemeOptionGetEvent($manager, 'default', 'fonts', true, []);
        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$event, ThemeOptionGetEvent::NAME],
                [$event, \sprintf('%s.%s', ThemeOptionGetEvent::NAME, 'fonts')]
            );

        $this->cache->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        self::assertSame([], $manager->getThemeOption('default', 'fonts'));
    }
}
