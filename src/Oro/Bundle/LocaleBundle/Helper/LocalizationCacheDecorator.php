<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizationCacheDecorator extends CacheProvider
{
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
     * @param string $id
     * @return false|Localization[]
     */
    public function fetch($id)
    {
        $cache = $this->cacheProvider->fetch($id);

        return $cache ? $this->unserializeLocalizations($cache) : false;
    }

    /**
     * @param string $id
     * @param Localization[] $data
     * @param int $lifeTime
     * @return bool
     */
    public function save($id, $data, $lifeTime = 0)
    {
        return $this->cacheProvider->save($id, $this->serializeLocalcations($data), $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        return $this->cacheProvider->fetch($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return $this->cacheProvider->contains($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        return $this->cacheProvider->save($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return $this->cacheProvider->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->cacheProvider->flushAll();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        return $this->cacheProvider->getStats();
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
