<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

class LocalizationSelectionType extends AbstractLocalizationSelectionType
{
    const NAME = 'oro_localization_selection';
    const LOCALIZATION_SELECTOR_CONFIG_KEY = 'oro_locale.allowed_localizations';

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

    /**
     * @return array
     */
    protected function getLocalizations()
    {
        return $this->localizationChoicesProvider->getLocalizationChoices();
    }
}
