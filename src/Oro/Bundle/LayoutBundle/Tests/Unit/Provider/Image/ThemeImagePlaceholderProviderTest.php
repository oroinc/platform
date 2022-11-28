<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider\Image;

use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProviderInterface;
use Oro\Bundle\LayoutBundle\Provider\Image\ThemeImagePlaceholderProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutContextStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ThemeImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    private LayoutContextStack|\PHPUnit\Framework\MockObject\MockObject $contextStack;

    private ThemeManager|\PHPUnit\Framework\MockObject\MockObject $themeManager;

    private ImagineUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject $imagineUrlProvider;

    private ThemeImagePlaceholderProvider $provider;

    protected function setUp(): void
    {
        $this->contextStack = $this->createMock(LayoutContextStack::class);
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->imagineUrlProvider = $this->createMock(ImagineUrlProviderInterface::class);

        $this->provider = new ThemeImagePlaceholderProvider(
            $this->contextStack,
            $this->themeManager,
            $this->imagineUrlProvider,
            'pl2'
        );
    }

    public function testGetPath(): void
    {
        $themeName = 'test_theme';

        $context = $this->createMock(LayoutContext::class);
        $context->expects(self::once())
            ->method('getOr')
            ->with('theme')
            ->willReturn($themeName);

        $this->contextStack->expects(self::once())
            ->method('getCurrentContext')
            ->willReturn($context);

        $theme = new Theme($themeName);
        $theme->setImagePlaceholders(['pl1' => '/path/to/pl1.img', 'pl2' => '/path/to/pl2.img']);

        $this->themeManager->expects(self::once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $filter = 'image_filter';
        $format = 'sample_format';

        $this->imagineUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with('/path/to/pl2.img', $filter, $format, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/path/to/filtered_pl2.img');

        self::assertEquals('/path/to/filtered_pl2.img', $this->provider->getPath($filter, $format));
    }

    public function getPathDataProvider(): array
    {
        return [
            'path unchanged if format is empty' => ['format' => '', 'expectedPath' => '/path/to/pl2.img'],
            'path unchanged if format is the same' => ['format' => 'img', 'expectedPath' => '/path/to/pl2.img'],
            'path with new extension if format is not the same' => [
                'format' => 'webp',
                'expectedPath' => '/path/to/pl2.img.webp',
            ],
        ];
    }

    public function testGetPathWithoutContext(): void
    {
        $this->contextStack->expects(self::once())
            ->method('getCurrentContext')
            ->willReturn(null);

        $this->themeManager->expects(self::never())
            ->method(self::anything());

        $this->imagineUrlProvider->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getPath('image_filter'));
    }

    public function testGetPathWithoutThemeName(): void
    {
        $context = $this->createMock(LayoutContext::class);
        $context->expects(self::once())
            ->method('getOr')
            ->with('theme')
            ->willReturn(null);

        $this->contextStack->expects(self::once())
            ->method('getCurrentContext')
            ->willReturn($context);

        $this->themeManager->expects(self::never())
            ->method(self::anything());

        $this->imagineUrlProvider->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getPath('image_filter'));
    }

    public function testGetPathWithoutPlaceholder(): void
    {
        $themeName = 'test_theme';

        $context = $this->createMock(LayoutContext::class);
        $context->expects(self::once())
            ->method('getOr')
            ->with('theme')
            ->willReturn($themeName);

        $this->contextStack->expects(self::once())
            ->method('getCurrentContext')
            ->willReturn($context);

        $theme = new Theme($themeName);
        $theme->setImagePlaceholders(['pl1' => '/path/to/pl1.img']);

        $this->themeManager->expects(self::once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $filter = 'image_filter';

        $this->imagineUrlProvider->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getPath($filter));
    }
}
