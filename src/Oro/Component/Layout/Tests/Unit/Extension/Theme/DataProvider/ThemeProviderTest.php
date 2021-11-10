<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\DataProvider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Component\Layout\Extension\Theme\DataProvider\ThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class ThemeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $themeManager;

    /** @var LocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationProvider;

    /** @var ThemeProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->localizationProvider = $this->createMock(LocalizationProviderInterface::class);

        $this->provider = new ThemeProvider($this->themeManager, $this->localizationProvider);
    }

    public function testGetIcon(): void
    {
        $themeName = 'test';
        $theme     = new Theme($themeName);
        $theme->setIcon('path/to/icon');

        $this->themeManager->expects(self::once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        self::assertSame('path/to/icon', $this->provider->getIcon($themeName));
    }

    public function testGetImagePlaceholders(): void
    {
        $themeName = 'test';
        $data = ['test_placeholder' => '/path/to/image.png'];

        $theme = new Theme($themeName);
        $theme->setImagePlaceholders($data);

        $this->themeManager->expects(self::once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        self::assertSame($data, $this->provider->getImagePlaceholders($themeName));
    }

    public function testGetStylesOutput(): void
    {
        $themeName = 'test';
        $theme     = new Theme($themeName);
        $theme->setConfig([
            'assets' => [
                'styles' => [
                    'output' => 'path/to/output/css'
                ],
                'styles_new' => [
                    'output' => 'path/to/output/css/new'
                ],
            ],
        ]);

        $this->themeManager->expects(self::once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        self::assertSame('build/test/path/to/output/css', $this->provider->getStylesOutput($themeName));
        self::assertSame(
            'build/test/path/to/output/css',
            $this->provider->getStylesOutput($themeName, 'styles')
        );
        self::assertSame(
            'build/test/path/to/output/css/new',
            $this->provider->getStylesOutput($themeName, 'styles_new')
        );
        self::assertSame(null, $this->provider->getStylesOutput($themeName, 'undefined section'));
    }

    public function testGetStylesOutputNull(): void
    {
        $themeName = 'test';
        $theme     = new Theme($themeName);

        $this->themeManager->expects(self::once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        self::assertNull($this->provider->getStylesOutput($themeName));
    }

    public function testGetStylesOutputWithFallback(): void
    {
        $grandParentThemeName = 'grand-parent';
        $grandParentTheme = new Theme($grandParentThemeName);
        $grandParentTheme->setConfig([
            'assets' => [
                'styles' => [
                    'output' => 'grand/parent/theme/path/to/output/css'
                ]
            ],
        ]);

        $parentThemeName = 'parent';
        $parentTheme = new Theme($parentThemeName, $grandParentThemeName);

        $themeName = 'theme';
        $theme = new Theme($themeName, $parentThemeName);

        $this->themeManager->expects(self::any())
            ->method('getTheme')
            ->withConsecutive([$themeName], [$parentThemeName], [$grandParentThemeName])
            ->willReturnOnConsecutiveCalls($theme, $parentTheme, $grandParentTheme);

        self::assertSame(
            'build/grand-parent/grand/parent/theme/path/to/output/css',
            $this->provider->getStylesOutput($themeName)
        );
        self::assertNull($this->provider->getStylesOutput($themeName, 'undefined'));
    }

    /**
     * @dataProvider stylesOutputDataProvider
     */
    public function testGetStylesOutputRtl(bool $themeRtl, bool $localRtl, string $output, string $expected): void
    {
        $themeName = 'test';

        $theme = new Theme($themeName);
        $theme->setRtlSupport($themeRtl);
        $theme->setConfig(['assets' => ['styles' => ['output' => $output]]]);

        $this->themeManager->expects(self::once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $localization = new Localization();
        $localization->setRtlMode($localRtl);

        $this->localizationProvider->expects(self::any())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        self::assertSame($expected, $this->provider->getStylesOutput($themeName, 'styles'));
        self::assertNull($this->provider->getStylesOutput($themeName, 'undefined_section'));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function stylesOutputDataProvider(): array
    {
        return [
            [
                'themeRtl' => false,
                'localizationRtl' => false,
                'output' => 'path/to/output/css',
                'expected' => 'build/test/path/to/output/css',
            ],
            [
                'themeRtl' => false,
                'localizationRtl' => false,
                'output' => 'path/to/output/css/new',
                'expected' => 'build/test/path/to/output/css/new',
            ],
            [
                'themeRtl' => false,
                'localizationRtl' => false,
                'output' => 'path/to/output/css.css',
                'expected' => 'build/test/path/to/output/css.css',
            ],
            [
                'themeRtl' => false,
                'localizationRtl' => false,
                'output' => 'path/to/output/css/new.css',
                'expected' => 'build/test/path/to/output/css/new.css',
            ],
            [
                'themeRtl' => true,
                'localizationRtl' => false,
                'output' => 'path/to/output/css',
                'expected' => 'build/test/path/to/output/css',
            ],
            [
                'themeRtl' => true,
                'localizationRtl' => false,
                'output' => 'path/to/output/css/new',
                'expected' => 'build/test/path/to/output/css/new',
            ],
            [
                'themeRtl' => true,
                'localizationRtl' => false,
                'output' => 'path/to/output/css.css',
                'expected' => 'build/test/path/to/output/css.css',
            ],
            [
                'themeRtl' => true,
                'localizationRtl' => false,
                'output' => 'path/to/output/css/new.css',
                'expected' => 'build/test/path/to/output/css/new.css',
            ],
            [
                'themeRtl' => false,
                'localizationRtl' => true,
                'output' => 'path/to/output/css',
                'expected' => 'build/test/path/to/output/css',
            ],
            [
                'themeRtl' => false,
                'localizationRtl' => true,
                'output' => 'path/to/output/css/new',
                'expected' => 'build/test/path/to/output/css/new',
            ],
            [
                'themeRtl' => false,
                'localizationRtl' => true,
                'output' => 'path/to/output/css.css',
                'expected' => 'build/test/path/to/output/css.css',
            ],
            [
                'themeRtl' => false,
                'localizationRtl' => true,
                'output' => 'path/to/output/css/new.css',
                'expected' => 'build/test/path/to/output/css/new.css',
            ],
            [
                'themeRtl' => true,
                'localizationRtl' => true,
                'output' => 'path/to/output/css',
                'expected' => 'build/test/path/to/output/css.rtl',
            ],
            [
                'themeRtl' => true,
                'localizationRtl' => true,
                'output' => 'path/to/output/css/new',
                'expected' => 'build/test/path/to/output/css/new.rtl',
            ],
            [
                'themeRtl' => true,
                'localizationRtl' => true,
                'output' => 'path/to/output/css.css',
                'expected' => 'build/test/path/to/output/css.rtl.css',
            ],
            [
                'themeRtl' => true,
                'localizationRtl' => true,
                'output' => 'path/to/output/css/new.css',
                'expected' => 'build/test/path/to/output/css/new.rtl.css',
            ],
        ];
    }
}
