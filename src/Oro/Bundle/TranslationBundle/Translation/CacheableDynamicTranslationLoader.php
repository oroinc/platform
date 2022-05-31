<?php

namespace Oro\Bundle\TranslationBundle\Translation;

/**
 * The loader that uses a cache to prevent unneeded loading of translations updated by a user.
 */
class CacheableDynamicTranslationLoader implements DynamicTranslationLoaderInterface
{
    private DynamicTranslationLoaderInterface $loader;
    private DynamicTranslationCache $cache;
    private TranslationsSanitizer $sanitizer;
    private TranslationMessageSanitizationErrorCollection $sanitizationError;

    public function __construct(
        DynamicTranslationLoaderInterface $loader,
        DynamicTranslationCache $cache,
        TranslationsSanitizer $sanitizer,
        TranslationMessageSanitizationErrorCollection $sanitizationError
    ) {
        $this->loader = $loader;
        $this->cache = $cache;
        $this->sanitizer = $sanitizer;
        $this->sanitizationError = $sanitizationError;
    }

    /**
     * {@inheritDoc}
     */
    public function loadTranslations(array $locales, bool $includeSystem): array
    {
        return $this->cache->get($locales, function (array $notCachedLocales) use ($includeSystem) {
            return $this->doLoadTranslations($notCachedLocales, $includeSystem);
        });
    }

    private function doLoadTranslations(array $locales, bool $includeSystem): array
    {
        $translations = $this->loader->loadTranslations($locales, $includeSystem);
        foreach ($translations as $locale => $data) {
            $errors = $this->sanitizer->sanitizeTranslations($data, $locale);
            foreach ($errors as $e) {
                $this->sanitizationError->add($e);
                $translations[$e->getLocale()][$e->getDomain()][$e->getMessageKey()] = $e->getSanitizedMessage();
            }
        }

        return $translations;
    }
}
