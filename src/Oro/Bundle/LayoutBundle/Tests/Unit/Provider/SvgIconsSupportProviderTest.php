<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider;

use Oro\Bundle\LayoutBundle\Provider\SvgIconsSupportProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

final class SvgIconsSupportProviderTest extends TestCase
{
    private ThemeManager|MockObject $themeManager;

    private CacheInterface&CacheItemPoolInterface $cache;

    private SvgIconsSupportProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->cache = new ArrayAdapter();

        $this->provider = new SvgIconsSupportProvider($this->themeManager, $this->cache);
    }

    public function testIsSvgIconsSupportedWhenCacheExists(): void
    {
        $themeName = 'default';

        $this->themeManager
            ->expects(self::never())
            ->method('hasTheme');

        $cacheItem = $this->cache->getItem('oro_layout.provider.svg_icons_support.theme.' . $themeName);
        $cacheItem->set(true);
        $this->cache->save($cacheItem);

        self::assertTrue($this->provider->isSvgIconsSupported($themeName));

        // Checks if cache works.
        self::assertTrue($this->provider->isSvgIconsSupported($themeName));
    }

    public function testIsSvgIconsSupportedWhenThemeNotExists(): void
    {
        $themeName = 'non_existent_theme';

        $this->themeManager
            ->expects(self::once())
            ->method('hasTheme')
            ->with($themeName)
            ->willReturn(false);

        self::assertFalse($this->provider->isSvgIconsSupported($themeName));

        // Checks if cache works.
        self::assertFalse($this->provider->isSvgIconsSupported($themeName));
    }

    /**
     * @dataProvider svgIconsSupportDataProvider
     */
    public function testIsSvgIconsSupportedWhenThemeExistsAndSupports(bool $isSupported): void
    {
        $themeName = 'default';
        $theme = new Theme($themeName);
        $theme->setSvgIconsSupport($isSupported);
        $themes = [$theme];

        $this->themeManager
            ->expects(self::once())
            ->method('hasTheme')
            ->with($themeName)
            ->willReturn(true);

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($themeName)
            ->willReturn($themes);

        self::assertSame($isSupported, $this->provider->isSvgIconsSupported($themeName));

        // Checks if cache works.
        self::assertSame($isSupported, $this->provider->isSvgIconsSupported($themeName));
    }

    public function svgIconsSupportDataProvider(): iterable
    {
        yield [true];
        yield [false];
    }

    public function testIsSvgIconsSupportedWhenMultipleThemes(): void
    {
        $parentTheme = new Theme('default');
        $parentTheme->setSvgIconsSupport(true);
        $themeName = 'custom';
        $theme2 = new Theme($themeName);
        $theme2->setSvgIconsSupport(null);
        $themes = [$parentTheme, $theme2];

        $this->themeManager
            ->expects(self::once())
            ->method('hasTheme')
            ->with($themeName)
            ->willReturn(true);

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($themeName)
            ->willReturn($themes);

        self::assertTrue($this->provider->isSvgIconsSupported($themeName));

        // Checks if cache works.
        self::assertTrue($this->provider->isSvgIconsSupported($themeName));
    }

    public function testIsSvgIconsSupportedWhenMultipleThemesAndNoOneSupports(): void
    {
        $parentTheme = new Theme('default');
        $parentTheme->setSvgIconsSupport(null);
        $themeName = 'custom';
        $theme2 = new Theme($themeName);
        $theme2->setSvgIconsSupport(null);
        $themes = [$parentTheme, $theme2];

        $this->themeManager
            ->expects(self::once())
            ->method('hasTheme')
            ->with($themeName)
            ->willReturn(true);

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($themeName)
            ->willReturn($themes);

        self::assertFalse($this->provider->isSvgIconsSupported($themeName));

        // Checks if cache works.
        self::assertFalse($this->provider->isSvgIconsSupported($themeName));
    }

    public function testReset(): void
    {
        $themeName = 'default';
        $theme = new Theme($themeName);
        $theme->setSvgIconsSupport(true);
        $themes = [$theme];

        $this->themeManager
            ->expects(self::exactly(2))
            ->method('hasTheme')
            ->with($themeName)
            ->willReturn(true);

        $this->themeManager
            ->expects(self::exactly(2))
            ->method('getThemesHierarchy')
            ->with($themeName)
            ->willReturn($themes);

        self::assertTrue($this->provider->isSvgIconsSupported($themeName));

        $this->provider->reset();

        // Checks if cache is cleared.
        self::assertTrue($this->provider->isSvgIconsSupported($themeName));
    }
}
