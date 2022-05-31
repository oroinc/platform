<?php

namespace Oro\Bundle\TranslationBundle\Translation;

/**
 * Represents a service to load translations updated by a user.
 */
interface DynamicTranslationLoaderInterface
{
    /**
     * Loads translations for the given locales.
     *
     * @param string[] $locales
     * @param bool $includeSystem Whether system translations should be loaded as well.
     *                            The system translations are translations stored source code, e.g. in YAML files.
     *
     * @return array [locale => [domain => [message id => message, ...], ...], ...]
     */
    public function loadTranslations(array $locales, bool $includeSystem): array;
}
