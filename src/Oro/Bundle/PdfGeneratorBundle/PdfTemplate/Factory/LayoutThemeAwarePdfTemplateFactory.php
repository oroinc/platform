<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplate\Factory;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplate;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Twig\Environment as TwigEnvironment;
use Twig\TemplateWrapper;

/**
 * Factory for creating a PDF template that is aware of the current layout theme.
 * Goes through the hierarchy of current theme to find the existing TWIG template.
 * Fallbacks to "default" theme if the template is not found in the hierarchy of current theme.
 */
class LayoutThemeAwarePdfTemplateFactory implements PdfTemplateFactoryInterface
{
    public function __construct(
        private TwigEnvironment $pdfTemplateTwigEnvironment,
        private ThemeConfigurationProvider $themeConfigurationProvider,
        private ThemeManager $themeManager
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return bool True if the template contains a theme placeholder.
     */
    #[\Override]
    public function isApplicable(TemplateWrapper|string $template, array $context = []): bool
    {
        return !$template instanceof TemplateWrapper && str_contains($template, '{{ themeName }}');
    }

    /**
     * Creates a PDF template, resolving the theme placeholder in the template name if necessary.
     *
     * {@inheritdoc}
     */
    #[\Override]
    public function createPdfTemplate(TemplateWrapper|string $template, array $context = []): PdfTemplateInterface
    {
        if ($template instanceof TemplateWrapper) {
            return new PdfTemplate($template, $context);
        }

        $defaultTheme = 'default';
        $themeName = $this->themeConfigurationProvider->getThemeName() ?: $defaultTheme;
        /** @var array<Theme> $themesHierarchy */
        $themesHierarchy = $this->themeManager->getThemesHierarchy($themeName);
        $twigLoader = $this->pdfTemplateTwigEnvironment->getLoader();
        foreach (array_reverse($themesHierarchy) as $theme) {
            $processedTemplate = $this->replacePlaceholder($template, $theme->getName());
            if ($twigLoader->exists($processedTemplate)) {
                break;
            }

            $processedTemplate = null;
        }

        if (!isset($processedTemplate)) {
            // Fallbacks to default theme if the template is not found in the hierarchy of current theme.
            $processedTemplate = $this->replacePlaceholder($template, $defaultTheme);
        }

        return new PdfTemplate($processedTemplate, $context);
    }

    private function replacePlaceholder(string $template, string $themeName): string
    {
        return strtr($template, ['{{ themeName }}' => $themeName]);
    }
}
