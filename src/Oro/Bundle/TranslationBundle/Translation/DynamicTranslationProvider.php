<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Oro\Bundle\TranslationBundle\Event\InvalidateDynamicTranslationCacheEvent;

/**
 * A service to get translations updated by a user.
 */
class DynamicTranslationProvider implements DynamicTranslationProviderInterface
{
    private DynamicTranslationLoaderInterface $loader;
    private DynamicTranslationCache $cache;

    /** @var string[] */
    private array $fallbackLocales = [];

    /** @var array [locale => [domain => [message id => message, ...], ...], ...] */
    private array $translations = [];

    public function __construct(DynamicTranslationLoaderInterface $loader, DynamicTranslationCache $cache)
    {
        $this->loader = $loader;
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslation(string $id, string $domain, string $locale): string
    {
        $this->ensureTranslationsLoaded($locale);

        if (!isset($this->translations[$locale][$domain][$id])) {
            throw new \LogicException(sprintf(
                'The translation "%s" -> "%s" (%s) does not exist.',
                $domain,
                $id,
                $locale
            ));
        }

        return $this->translations[$locale][$domain][$id];
    }

    /**
     * {@inheritDoc}
     */
    public function hasTranslation(string $id, string $domain, string $locale): bool
    {
        $this->ensureTranslationsLoaded($locale);

        return isset($this->translations[$locale][$domain][$id]);
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslations(string $domain, string $locale): array
    {
        $this->ensureTranslationsLoaded($locale);

        return $this->translations[$locale][$domain] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function setFallbackLocales(array $locales): void
    {
        $this->fallbackLocales = $locales;
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(array $locales): void
    {
        $this->cache->delete($locales);
        foreach ($locales as $locale) {
            unset($this->translations[$locale]);
        }
        $this->loadTranslations($locales);
    }

    public function onClearCache(InvalidateDynamicTranslationCacheEvent $event): void
    {
        foreach ($event->getLocales() as $locale) {
            unset($this->translations[$locale]);
        }
    }

    private function ensureTranslationsLoaded(string $locale): void
    {
        if (isset($this->translations[$locale])) {
            return;
        }

        $locales = array_diff(
            array_unique(array_merge($this->fallbackLocales, [$locale])),
            array_keys($this->translations)
        );
        if ($locales) {
            $this->loadTranslations($locales);
        }
    }

    private function loadTranslations(array $locales): void
    {
        $data = $this->loader->loadTranslations($locales, false);
        foreach ($data as $loc => $trans) {
            $this->translations[$loc] = $trans;
        }
        foreach ($locales as $loc) {
            if (!isset($this->translations[$loc])) {
                $this->translations[$loc] = [];
            }
        }
    }
}
