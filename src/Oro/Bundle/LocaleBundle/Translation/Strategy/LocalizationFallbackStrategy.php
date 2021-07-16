<?php

namespace Oro\Bundle\LocaleBundle\Translation\Strategy;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Provides a tree of locale fallbacks configured by a user.
 */
class LocalizationFallbackStrategy implements TranslationStrategyInterface, CacheWarmerInterface
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
            $fallbacks = $this->getActualLocaleFallbacks();

            $this->cacheProvider->save(static::CACHE_KEY, $fallbacks);
        }

        return $fallbacks;
    }

    private function getActualLocaleFallbacks(): array
    {
        /** All localizations always should have only one parent that equals to default language */
        return [
            Configuration::DEFAULT_LOCALE => array_reduce(
                $this->getRootLocalizations(),
                function ($result, Localization $localization) {
                    return array_merge($result, $this->localizationToArray($localization));
                },
                []
            )
        ];
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

    /**
     * @{@inheritdoc}
     */
    public function warmUp($cacheDir): void
    {
        try {
            $this->cacheProvider->save(static::CACHE_KEY, $this->getActualLocaleFallbacks());
        } catch (InvalidFieldNameException $exception) {
            // Cache warming can be used during upgrade from the app version where not all required columns yet exist.
            // Silently skips warming of locale fallbacks in this case, considering as not an error.
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return true;
    }
}
