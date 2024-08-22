<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Bundle\LayoutBundle\Provider\SvgIconsSupportProvider;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\OptionsResolver\Options;

/**
 * Sets "is_svg_icons_support" variable to the layout context if the current layout theme
 * supports SVG icons rendering.
 */
class SvgIconsSupportContextConfigurator implements ContextConfiguratorInterface
{
    public function __construct(private SvgIconsSupportProvider $svgIconsSupportProvider)
    {
    }

    public function configureContext(ContextInterface $context): void
    {
        $context
            ->getResolver()
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
                        if (!$themeName) {
                            return false;
                        }

                        return $this->svgIconsSupportProvider->isSvgIconsSupported($themeName);
                    },
                ]
            )
            ->setAllowedTypes('is_svg_icons_support', ['boolean']);
    }
}
