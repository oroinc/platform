<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Component\OptionsResolver\Options;

/**
 * Sets "is_svg_icons_support" variable to the layout context if the current layout theme
 * supports SVG icons rendering.
 */
class SvgIconsSupportContextConfigurator implements ContextConfiguratorInterface
{
    public function __construct(private ThemeManager $themeManager)
    {
    }

    public function configureContext(ContextInterface $context): void
    {
        $context->getResolver()
            ->setDefaults(
                [
                    'is_svg_icons_support' => function (Options $options, $value) {
                        if (null !== $value) {
                            return $value;
                        }

                        if (!$options->offsetExists('theme')) {
                            return false;
                        }

                        $themeName = $options->offsetGet('theme');
                        if (!$themeName || !$this->themeManager->hasTheme($themeName)) {
                            return false;
                        }

                        $theme = $this->themeManager->getTheme($themeName);

                        return $theme->isSvgIconsSupport();
                    }
                ]
            )
            ->setAllowedTypes('is_svg_icons_support', ['boolean']);
    }
}
