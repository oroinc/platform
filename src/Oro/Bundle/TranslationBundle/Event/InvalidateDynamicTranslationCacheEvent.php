<?php

namespace Oro\Bundle\TranslationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is dispatched after clearing of the dynamic translation cache.
 */
class InvalidateDynamicTranslationCacheEvent extends Event
{
    public const NAME = 'oro_translation.invalidate_dynamic_translation_cache';

    private array $locales;

    public function __construct(array $locales)
    {
        $this->locales = $locales;
    }

    /**
     * @return string[]
     */
    public function getLocales(): array
    {
        return $this->locales;
    }
}
