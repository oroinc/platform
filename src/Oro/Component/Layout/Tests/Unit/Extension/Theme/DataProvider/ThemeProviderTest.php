<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\DataProvider;

use Oro\Bundle\DistributionBundle\Provider\PublicDirectoryProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Component\Layout\Extension\Theme\DataProvider\ThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Psr\Log\LoggerInterface;

class ThemeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $themeManager;

    /** @var LocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationProvider;

    /** @var ThemeProvider */
    private $provider;

    /** @var PublicDirectoryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $publicDirectoryProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->localizationProvider = $this->createMock(LocalizationProviderInterface::class);
        $this->publicDirectoryProvider = $this->createMock(PublicDirectoryProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new ThemeProvider(
            $this->themeManager,
            $this->localizationProvider,
            $this->publicDirectoryProvider,
        );

        $this->provider->setLogger($this->logger);

        $this->publicDirectory = sys_get_temp_dir() . '/mocked_public_directory_' . uniqid('', true);

        if (is_dir($this->publicDirectory)) {
            $this->removeDirectory($this->publicDirectory);
        }

        mkdir($this->publicDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->publicDirectory)) {
            $this->removeDirectory($this->publicDirectory);
        }
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

    public function testGetStylesOutputContentWhenFileExists(): void
    {
        $themeName = 'test';
        $sectionName = 'styles';
        $outputPath = 'css/test.css';
        $fileContent = 'body { background: #fff; }';
        $filePath = $this->publicDirectory . '/build/' . $themeName . '/' . $outputPath;

        $this->createFileWithContent($filePath, $fileContent);

        $this->publicDirectoryProvider
            ->method('getPublicDirectory')
            ->willReturn($this->publicDirectory);

        $theme = new Theme($themeName);
        $theme->setConfig(['assets' => ['styles' => ['output' => $outputPath]]]);

        $this->themeManager->expects(self::once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $result = $this->provider->getStylesOutputContent($themeName, $sectionName);

        self::assertSame($fileContent, $result, 'The file content does not match the expected content.');
    }

    public function testGetStylesOutputContentWhenFileDoesNotExist(): void
    {
        $themeName = 'test';
        $sectionName = 'styles';
        $outputPath = 'css/nonexistent.css';

        $filePath = $this->publicDirectory . '/build/' . $themeName . '/' . $outputPath;

        $this->publicDirectoryProvider
            ->method('getPublicDirectory')
            ->willReturn($this->publicDirectory);

        $theme = new Theme($themeName);
        $theme->setConfig(['assets' => ['styles' => ['output' => $outputPath]]]);

        $this->themeManager->expects(self::once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains('CSS file not found'),
                self::callback(function ($context) use ($filePath, $themeName, $sectionName) {
                    return isset($context['filePath'], $context['themeName'], $context['sectionName'])
                        && $context['filePath'] === $filePath
                        && $context['themeName'] === $themeName
                        && $context['sectionName'] === $sectionName;
                })
            );

        $result = $this->provider->getStylesOutputContent($themeName, $sectionName);
        self::assertSame('', $result);
    }


    public function testGetStylesOutputContentWhenOutputPathIsNull(): void
    {
        $themeName = 'test';
        $sectionName = 'styles';

        $this->publicDirectoryProvider
            ->method('getPublicDirectory')
            ->willReturn($this->publicDirectory);

        $theme = new Theme($themeName);
        $theme->setConfig(['assets' => ['styles' => []]]);

        $this->themeManager->expects(self::once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $result = $this->provider->getStylesOutputContent($themeName, $sectionName);

        self::assertSame('', $result);
    }

    private function createFileWithContent(string $filePath, string $content): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($filePath, $content);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $directory . '/' . $file;
            is_dir($filePath) ? $this->removeDirectory($filePath) : unlink($filePath);
        }
        rmdir($directory);
    }
}
