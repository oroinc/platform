<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider\Image;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Bundle\LayoutBundle\Provider\Image\ThemeImagePlaceholderProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ThemeImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutContextHolder|\PHPUnit\Framework\MockObject\MockObject */
    private $contextHolder;

    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $themeManager;

    /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject */
    private $imagineCacheManager;

    /** @var string */
    private $placeholderName = 'pl2';

    /** @var ThemeImagePlaceholderProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->contextHolder = $this->createMock(LayoutContextHolder::class);
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->imagineCacheManager = $this->createMock(CacheManager::class);

        $this->provider = new ThemeImagePlaceholderProvider(
            $this->contextHolder,
            $this->themeManager,
            $this->imagineCacheManager,
            $this->placeholderName
        );
    }

    public function testGetPath(): void
    {
        $themeName = 'test_theme';

        $context = $this->createMock(LayoutContext::class);
        $context->expects($this->once())
            ->method('getOr')
            ->with('theme')
            ->willReturn($themeName);

        $this->contextHolder->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $theme = new Theme($themeName);
        $theme->setImagePlaceholders(['pl1' => '/path/to/pl1.img', 'pl2' => '/path/to/pl2.img']);

        $this->themeManager->expects($this->once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $filter = 'image_filter';

        $this->imagineCacheManager->expects($this->once())
            ->method('generateUrl')
            ->with('/path/to/pl2.img', $filter, [], null, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/path/to/filtered_pl2.img');

        $this->assertEquals('/path/to/filtered_pl2.img', $this->provider->getPath($filter));
    }

    public function testGetPathWithoutContext(): void
    {
        $this->contextHolder->expects($this->once())
            ->method('getContext')
            ->willReturn(null);

        $this->themeManager->expects($this->never())
            ->method($this->anything());

        $this->imagineCacheManager->expects($this->never())
            ->method($this->anything());

        $this->assertNull($this->provider->getPath('image_filter'));
    }

    public function testGetPathWithoutThemeName(): void
    {
        $context = $this->createMock(LayoutContext::class);
        $context->expects($this->once())
            ->method('getOr')
            ->with('theme')
            ->willReturn(null);

        $this->contextHolder->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $this->themeManager->expects($this->never())
            ->method($this->anything());

        $this->imagineCacheManager->expects($this->never())
            ->method($this->anything());

        $this->assertNull($this->provider->getPath('image_filter'));
    }

    public function testGetPathWithoutPlaceholder(): void
    {
        $themeName = 'test_theme';

        $context = $this->createMock(LayoutContext::class);
        $context->expects($this->once())
            ->method('getOr')
            ->with('theme')
            ->willReturn($themeName);

        $this->contextHolder->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $theme = new Theme($themeName);
        $theme->setImagePlaceholders(['pl1' => '/path/to/pl1.img']);

        $this->themeManager->expects($this->once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $filter = 'image_filter';

        $this->imagineCacheManager->expects($this->never())
            ->method($this->anything());

        $this->assertNull($this->provider->getPath($filter));
    }
}
