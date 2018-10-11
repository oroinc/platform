<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

/**
 * Default language provider is used as a fallback for entities which are not supported by other providers.
 * Should be added with the lowest priority to be executed last.
 */
class DefaultPreferredLanguageProvider implements PreferredLanguageProviderInterface
{
    /**
     * @var LocaleSettings
     */
    private $localeSettings;

    /**
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($entity): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getPreferredLanguage($entity): string
    {
        return $this->localeSettings->getLanguage();
    }
}
