<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\Common\Cache\CacheProvider;

class OrmTranslationMetadataCache
{
    /**
     * @var array
     */
    protected $localCache;

    /**
     * @var CacheProvider
     */
    protected $cacheImpl;

    /**
     * Constructor
     *
     * @param CacheProvider $cacheImpl
     */
    public function __construct(CacheProvider $cacheImpl)
    {
        $this->cacheImpl = $cacheImpl;
        $this->localCache = [];
    }

    /**
     * Gets the timestamp of the last update of database translations for the given locale
     *
     * @param string $locale
     * @return int|bool timestamp or false if the timestamp is not cached yet
     */
    public function getTimestamp($locale)
    {
        if (!isset($this->localCache[$locale])) {
            $this->localCache[$locale] = $this->cacheImpl->fetch($locale);
        }

        return $this->localCache[$locale];
    }

    /**
     * Renews the timestamp of the last update of database translations for the given locale
     *
     * @param string $locale
     */
    public function updateTimestamp($locale)
    {
        $this->localCache[$locale] = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();
        $this->cacheImpl->save($locale, $this->localCache[$locale]);
    }
}
