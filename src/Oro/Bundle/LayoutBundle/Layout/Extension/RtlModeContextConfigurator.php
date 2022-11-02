<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Component\OptionsResolver\Options;

/**
 * Sets "is_rtl_mode_enabled" variable to the layout context if the current layout theme and current localization
 * support right-to-left text direction.
 */
class RtlModeContextConfigurator implements ContextConfiguratorInterface
{
    private ThemeManager $themeManager;

    private LocalizationProviderInterface $localizationProvider;

    public function __construct(ThemeManager $themeManager, LocalizationProviderInterface $localizationProvider)
    {
        $this->themeManager = $themeManager;
        $this->localizationProvider = $localizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context): void
    {
        $context->getResolver()
            ->setDefaults(
                [
                    'is_rtl_mode_enabled' => function (Options $options, $value) {
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

                        $localization = $this->localizationProvider->getCurrentLocalization();
                        if (!$localization) {
                            return false;
                        }

                        return $theme->isRtlSupport() && $localization->isRtlMode();
                    }
                ]
            )
            ->setAllowedTypes('is_rtl_mode_enabled', ['boolean']);
    }
}
