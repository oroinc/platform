<?php

namespace Oro\Bundle\TranslationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that fires during clearing the translation cache
 */
class InvalidateTranslationCacheEvent extends Event
{
    const NAME = 'oro_translation.invalidate_translation_cache';

    /**
     * @var string|null
     */
    private $locale;

    /**
     * @param string|null $locale
     */
    public function __construct(string $locale = null)
    {
        $this->locale = $locale;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }
}
