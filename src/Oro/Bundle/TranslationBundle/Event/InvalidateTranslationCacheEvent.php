<?php

namespace Oro\Bundle\TranslationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

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

    public function __construct(string $locale = null)
    {
        $this->locale = $locale;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }
}
