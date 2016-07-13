<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

class EnabledLocalizationSelectionType extends AbstractLocalizationSelectionType
{
    const NAME = 'oro_locale_enabled_localization_selection';
    const LOCALIZATION_SELECTOR_CONFIG_KEY = 'oro_locale.enabled_localizations';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalizationSelectorConfigKey()
    {
        return static::LOCALIZATION_SELECTOR_CONFIG_KEY;
    }
}
