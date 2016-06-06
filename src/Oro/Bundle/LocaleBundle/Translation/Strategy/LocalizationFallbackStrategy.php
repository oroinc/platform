<?php

namespace Oro\Bundle\LocaleBundle\Translation\Strategy;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class LocalizationFallbackStrategy implements TranslationStrategyInterface
{
    const NAME = 'oro_localalization_fallback_strategy';
    const CACHE_KEY = 'localization_fallbacks';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @param ManagerRegistry $registry
     * @param CacheProvider $cacheProvider
     */
    public function __construct(ManagerRegistry $registry, CacheProvider $cacheProvider)
    {
        $this->registry = $registry;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocaleFallbacks()
    {
        $key = static::CACHE_KEY;
        if ($this->cacheProvider->contains($key)) {
            return $this->cacheProvider->fetch($key);
        }
        $fallbacks = array_reduce($this->getRootLocalizations(), function ($result, Localization $localization) {
            return array_merge($result, $this->localizationToArray($localization));
        }, []);
        $this->cacheProvider->save($key, $fallbacks);
        return $fallbacks;
    }

    public function clearCache()
    {
        $this->cacheProvider->delete(static::CACHE_KEY);
    }

    /**
     * @return array|Localization[]
     */
    protected function getRootLocalizations()
    {
        return $this->registry->getManagerForClass($this->entityClass)
            ->getRepository($this->entityClass)->findRootsWithChildren();
    }

    /**
     * @param Localization $localization
     * @return array
     */
    protected function localizationToArray(Localization $localization)
    {
        $children = [];
        foreach ($localization->getChildLocalizations() as $child) {
            $children = array_merge($children, $this->localizationToArray($child));
        }
        return [$localization->getLanguageCode() => $children];
    }
}
