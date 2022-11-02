<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationLoaderInterface;

class DynamicTranslationLoaderStub implements DynamicTranslationLoaderInterface
{
    /** @var array [locale => [domain => [message id => message, ...], ...], ...] */
    private array $translations;

    public function __construct(array $translations = [])
    {
        $this->translations = $translations;
    }

    /**
     * {@inheritDoc}
     */
    public function loadTranslations(array $locales, bool $includeSystem): array
    {
        $result = [];
        foreach ($locales as $locale) {
            if (isset($this->translations[$locale])) {
                $result[$locale] = $this->translations[$locale];
            }
        }

        return $result;
    }
}
