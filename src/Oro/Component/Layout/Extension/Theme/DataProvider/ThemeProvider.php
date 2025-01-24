<?php

namespace Oro\Component\Layout\Extension\Theme\DataProvider;

use Oro\Bundle\DistributionBundle\Provider\PublicDirectoryProvider;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Provides theme icon and path to css files in theme by passed styles entry point
 */
class ThemeProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array<string, Theme> */
    private array $themes = [];

    public function __construct(
        private readonly ThemeManager $themeManager,
        private readonly LocalizationProviderInterface $localizationProvider,
        private readonly PublicDirectoryProvider $publicDirectoryProvider,
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * @param string $themeName
     *
     * @return string
     */
    public function getIcon($themeName)
    {
        return $this->getTheme($themeName)->getIcon();
    }

    /**
     * @param string $themeName
     *
     * @return string
     */
    public function getLogo($themeName)
    {
        return $this->getTheme($themeName)->getLogo();
    }

    public function getLogoSmall(string $themeName): ?string
    {
        return $this->getTheme($themeName)?->getLogoSmall();
    }

    /**
     * @param string $themeName
     *
     * @return array
     */
    public function getImagePlaceholders($themeName): array
    {
        return $this->getTheme($themeName)->getImagePlaceholders();
    }

    public function getStylesOutput(string $themeName, string $sectionName = 'styles'): ?string
    {
        $outputPath = $this->getOutputPath($themeName, $sectionName);
        if ($outputPath) {
            return sprintf('build/%s/%s', $themeName, $outputPath);
        }

        $parentTheme = $this->getTheme($themeName)->getParentTheme();
        if ($parentTheme) {
            return $this->getStylesOutput($parentTheme, $sectionName);
        }

        return null;
    }

    /**
     * @param string $themeName
     *
     * @return Theme
     */
    private function getTheme($themeName)
    {
        if (!array_key_exists($themeName, $this->themes)) {
            $this->themes[$themeName] = $this->themeManager->getTheme($themeName);
        }

        return $this->themes[$themeName];
    }

    private function getOutputPath(string $themeName, string $sectionName): ?string
    {
        $theme = $this->getTheme($themeName);

        $output = $theme->getConfigByKey('assets')[$sectionName]['output'] ?? null;
        if (!$output) {
            return null;
        }

        if (!$theme->isRtlSupport()) {
            return $output;
        }

        $localization = $this->localizationProvider->getCurrentLocalization();
        if (!$localization || !$localization->isRtlMode()) {
            return $output;
        }

        preg_match('/^(?<path>.+)(?<extension>\.[\w\-]*)?$/Uui', $output, $matches);

        return sprintf('%s.rtl%s', $matches['path'], $matches['extension'] ?? '');
    }

    public function getStylesOutputContent(string $themeName, string $sectionName): string
    {
        $outputPath = $this->getStylesOutput($themeName, $sectionName);

        if ($outputPath === null) {
            return '';
        }

        $filePath = sprintf('%s/%s', $this->publicDirectoryProvider->getPublicDirectory(), $outputPath);

        if (!is_file($filePath) || !is_readable($filePath)) {
            $this->logger->error(
                'CSS file not found: {filePath}. Theme: "{themeName}", Section: "{sectionName}". ' .
                'Ensure the file exists and is readable.',
                ['filePath' => $filePath, 'themeName' => $themeName, 'sectionName' => $sectionName]
            );
            return '';
        }

        return file_get_contents($filePath);
    }
}
