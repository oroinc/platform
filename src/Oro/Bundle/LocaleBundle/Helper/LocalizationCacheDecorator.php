<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\Serializer\SerializerInterface;

use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizationCacheDecorator extends CacheProvider
{
    const SERIALIZATION_FORMAT = 'array';

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param CacheProvider       $cacheProvider
     * @param SerializerInterface $serializer
     */
    public function __construct(
        CacheProvider $cacheProvider,
        SerializerInterface $serializer
    ) {
        $this->cacheProvider = $cacheProvider;
        $this->serializer = $serializer;
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

        if (!$cache) {
            return false;
        }

        $cache = $this->unserializeLocalizations($cache);

        if (count($cache) === 1) {
            $this->resolveParent(reset($cache));

            return $cache;
        }

        $this->resolveParents($cache);

        return $cache;
    }

    /**
     * @param string         $id
     * @param Localization[] $data
     * @param int            $lifeTime
     * @return bool
     */
    public function save($id, $data, $lifeTime = 0)
    {
        return $this->cacheProvider->save($id, $this->serializeLocalizations($data), $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->cacheProvider->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        return $this->cacheProvider->deleteAll();
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
     * @param string[] $localizations
     * @return Localization[]
     */
    private function unserializeLocalizations($localizations)
    {
        return array_map(function ($element) {
            return $this->serializer->deserialize($element, Localization::class, static::SERIALIZATION_FORMAT);
        }, $localizations);
    }

    /**
     * @param Localization[] $localizations
     * @return array
     */
    private function serializeLocalizations($localizations)
    {
        return array_map(function ($element) {
            return $this->serializer->serialize($element, static::SERIALIZATION_FORMAT);
        }, $localizations);
    }

    /**
     * @param Localization[] $cache
     */
    private function resolveParents(array $cache)
    {
        /** @var Localization $localization */
        foreach ($cache as $localization) {
            if ($localization->getParentLocalization()
                && array_key_exists($localization->getParentLocalization()->getId(), $cache)
            ) {
                $localization->setParentLocalization($cache[$localization->getParentLocalization()->getId()]);
            }
        }
    }

    /**
     * @param Localization $localization
     */
    private function resolveParent(Localization $localization)
    {
        if (!$localization->getParentLocalization()) {
            return;
        }

        $cacheKey = LocalizationManager::getCacheKey($localization->getParentLocalization()->getId());
        $cache = $this->cacheProvider->fetch($cacheKey);

        if (!$cache) {
            return;
        }

        $cache = $this->unserializeLocalizations($cache);
        $parentLocalization = reset($cache);
        $localization->setParentLocalization($parentLocalization);

        $this->resolveParent($parentLocalization);
    }
}
