<?php

namespace Oro\Component\Layout\Extension\Theme\DataProvider;

use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

/**
 * Provides theme icon and path to css files in theme by passed styles entry point
 */
class ThemeProvider
{
    protected ThemeManager $themeManager;

    protected LocalizationProviderInterface $localizationProvider;

    /** @var Theme[] */
    protected $themes = [];

    public function __construct(ThemeManager $themeManager, LocalizationProviderInterface $localizationProvider)
    {
        $this->themeManager = $themeManager;
        $this->localizationProvider = $localizationProvider;
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
}
