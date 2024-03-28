<?php

namespace Oro\Bundle\LayoutBundle\Twig;

use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider as GeneralThemeConfigurationProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extends twig with theme configuration provider
 */
class ThemeConfigurationTwigExtension extends AbstractExtension
{
    public function __construct(private GeneralThemeConfigurationProvider $themeConfigurationProvider)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_theme_configuration_value', [$this, 'getThemeConfigurationValue']),
        ];
    }

    public function getThemeConfigurationValue(string $option): mixed
    {
        return $this->themeConfigurationProvider->getThemeConfigurationOption($option);
    }
}
