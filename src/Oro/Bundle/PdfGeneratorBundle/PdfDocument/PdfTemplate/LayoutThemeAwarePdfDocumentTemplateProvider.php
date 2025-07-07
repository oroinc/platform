<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfTemplate;

use Oro\Bundle\PdfGeneratorBundle\Layout\Extension\PdfDocumentTemplatesThemeConfigurationExtension;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Twig\TemplateWrapper;

/**
 * Theme-aware PDF document template provider.
 *
 * Retrieves the path to the appropriate Twig template based on the current layout theme.
 * Falls back to a parent theme if the template is not defined in the current theme.
 */
class LayoutThemeAwarePdfDocumentTemplateProvider implements PdfDocumentTemplateProviderInterface
{
    public function __construct(
        private readonly ThemeConfigurationProvider $themeConfigurationProvider,
        private readonly ThemeManager $themeManager
    ) {
    }

    #[\Override]
    public function getContentTemplate(string $pdfDocumentType): TemplateWrapper|string|null
    {
        return $this->getTemplatePathFromThemeConfig(
            $pdfDocumentType,
            PdfDocumentTemplatesThemeConfigurationExtension::CONTENT_TEMPLATE
        );
    }

    #[\Override]
    public function getHeaderTemplate(string $pdfDocumentType): TemplateWrapper|string|null
    {
        return $this->getTemplatePathFromThemeConfig(
            $pdfDocumentType,
            PdfDocumentTemplatesThemeConfigurationExtension::HEADER_TEMPLATE
        );
    }

    #[\Override]
    public function getFooterTemplate(string $pdfDocumentType): TemplateWrapper|string|null
    {
        return $this->getTemplatePathFromThemeConfig(
            $pdfDocumentType,
            PdfDocumentTemplatesThemeConfigurationExtension::FOOTER_TEMPLATE
        );
    }

    private function getTemplatePathFromThemeConfig(string $pdfDocumentType, string $templateType): ?string
    {
        $themeName = $this->themeConfigurationProvider->getThemeName();
        $themes = $this->themeManager->getThemesHierarchy($themeName);

        foreach (array_reverse($themes) as $theme) {
            $pdfDocumentThemeConfig = $theme->getConfigByKey(
                PdfDocumentTemplatesThemeConfigurationExtension::PDF_DOCUMENT,
                []
            );
            if (isset($pdfDocumentThemeConfig[$pdfDocumentType][$templateType])) {
                return $pdfDocumentThemeConfig[$pdfDocumentType][$templateType];
            }
        }

        return null;
    }
}
