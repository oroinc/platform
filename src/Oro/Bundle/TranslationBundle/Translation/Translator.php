<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;

class Translator extends BaseTranslator
{
    /**
     * @var array
     */
    private $dynamicResources = [];

    /**
     * Collector of translations
     *
     * Collects all translations for corresponded domains and locale,
     * takes in account fallback of locales.
     * Method is used for exposing of collected translations.
     *
     * @param array       $domains list of required domains, by default empty, means all domains
     * @param string|null $locale  locale of translations, by default is current locale
     * @return array
     */
    public function getTranslations(array $domains = array(), $locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }

        if (!isset($this->catalogues[$locale])) {
            $this->loadCatalogue($locale);
        }

        $fallbackCatalogues   = array();
        $fallbackCatalogues[] = $catalogue = $this->catalogues[$locale];
        while ($catalogue = $catalogue->getFallbackCatalogue()) {
            $fallbackCatalogues[] = $catalogue;
        }

        $domains      = array_flip($domains);
        $translations = array();
        for ($i = count($fallbackCatalogues) - 1; $i >= 0; $i--) {
            $localeTranslations = $fallbackCatalogues[$i]->all();
            // if there are domains -> filter only their translations
            if ($domains) {
                $localeTranslations = array_intersect_key($localeTranslations, $domains);
            }
            foreach ($localeTranslations as $domain => $domainTranslations) {
                if (!empty($translations[$domain])) {
                    $translations[$domain] = array_merge($translations[$domain], $domainTranslations);
                } else {
                    $translations[$domain] = $domainTranslations;
                }
            }
        }

        return $translations;
    }

    /**
     * Checks if the given message has a translation.
     *
     * @param string $id         The message id (may also be an object that can be cast to string)
     * @param string $domain     The domain for the message
     * @param string $locale     The locale
     *
     * @return string The translated string
     */
    public function hasTrans($id, $domain = null, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }

        if (null === $domain) {
            $domain = 'messages';
        }

        if (!isset($this->catalogues[$locale])) {
            $this->loadCatalogue($locale);
        }

        $catalogue = $this->catalogues[$locale];
        $result = $catalogue->defines($id, $domain);
        while (!$result && $catalogue = $catalogue->getFallbackCatalogue()) {
            $result = $catalogue->defines($id, $domain);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function addResource($format, $resource, $locale, $domain = null)
    {
        // remember dynamic resources
        if ($resource instanceof DynamicResourceInterface) {
            if (!isset($this->dynamicResources[$locale])) {
                $this->dynamicResources[$locale] = [];
            }
            $this->dynamicResources[$locale][] = $resource;
        }

        parent::addResource($format, $resource, $locale, $domain);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadCatalogue($locale)
    {
        // check if any dynamic resource is changed and update translation catalogue if needed
        if (!empty($this->dynamicResources)
            && isset($this->dynamicResources[$locale])
            && !empty($this->dynamicResources[$locale])
        ) {
            $catalogueFile = $this->options['cache_dir'] . '/catalogue.' . $locale . '.php';
            if (is_file($catalogueFile)) {
                $time = filemtime($catalogueFile);
                /** @var DynamicResourceInterface $dynamicResource */
                foreach ($this->dynamicResources[$locale] as $dynamicResource) {
                    if (!$dynamicResource->isFresh($time)) {
                        // remove translation catalogue to allow parent class to rebuild it
                        unlink($catalogueFile);
                        break;
                    }
                }
            }
        }

        parent::loadCatalogue($locale);
    }
}
