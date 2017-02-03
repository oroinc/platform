<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizationCacheHelper
{
    const CACHE_NAMESPACE = 'ORO_LOCALE_LOCALIZATION_DATA';

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * Gets array of Localization or false, if there is no cache
     *
     * @return Localization[]|false
     */
    public function get()
    {
        $cache = $this->cacheProvider->fetch(self::CACHE_NAMESPACE);

        if ($cache) {
            return $this->unserializeLocalizations($cache);
        }

        return false;
    }

    /**
     * @param Localization[] $cache
     * @return bool
     */
    public function save(array $cache)
    {
        return $this->cacheProvider->save(self::CACHE_NAMESPACE, $this->serializeLocalcations($cache));
    }

    /**
     * @param string $element
     * @return Localization
     * @TODO - move unserialization to separate class, after that update test @BAP-13604
     */
    private function unserialize($element)
    {
        return unserialize($element);
    }

    /**
     * @param Localization $element
     * @return string
     * @TODO - move serialization to separate class, after that update test @BAP-13604
     */
    private function serialize(Localization $element)
    {
        return serialize($element);
    }

    /**
     * @param string[] $localizations
     * @return Localization[]
     */
    private function unserializeLocalizations($localizations)
    {
        return array_map(function ($element) {
            return $this->unserialize($element);
        }, $localizations);
    }

    /**
     * @param Localization[] $localizations
     * @return array
     */
    private function serializeLocalcations($localizations)
    {
        return array_map(function ($element) {
            return $this->serialize($element);
        }, $localizations);
    }
}
