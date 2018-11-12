<?php

namespace Oro\Bundle\LocaleBundle\Translation\Strategy;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;

/**
 * Provides a tree of locale fallbacks configured by a user.
 */
class LocalizationFallbackStrategy implements TranslationStrategyInterface
{
    const NAME = 'oro_localization_fallback_strategy';
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
     * {@inheritdoc}
     */
    public function isApplicable()
    {
        return true;
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
        $fallbacks = $this->cacheProvider->fetch(static::CACHE_KEY);
        if (false === $fallbacks) {
            /** All localizations always should have only one parent that equals to default language */
            $fallbacks = [
                Configuration::DEFAULT_LOCALE => array_reduce(
                    $this->getRootLocalizations(),
                    function ($result, Localization $localization) {
                        return array_merge($result, $this->localizationToArray($localization));
                    },
                    []
                )
            ];

            $this->cacheProvider->save(static::CACHE_KEY, $fallbacks);
        }

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
        /** @var LocalizationRepository $repository */
        $repository = $this->registry->getManagerForClass($this->entityClass)->getRepository($this->entityClass);

        return $repository->findRootsWithChildren();
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
