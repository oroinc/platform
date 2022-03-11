<?php

namespace Oro\Bundle\LocaleBundle\Translation\Strategy;

use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Provides a tree of locale fallbacks configured by a user.
 */
class LocalizationFallbackStrategy implements TranslationStrategyInterface, CacheWarmerInterface
{
    public const NAME = 'oro_localization_fallback_strategy';
    private const CACHE_KEY = 'localization_fallbacks';

    protected ManagerRegistry $registry;
    protected CacheInterface $cacheProvider;
    protected string $entityClass;

    public function __construct(ManagerRegistry $registry, CacheInterface $cacheProvider)
    {
        $this->registry = $registry;
        $this->cacheProvider = $cacheProvider;
    }

    public function isApplicable(): bool
    {
        return true;
    }

    public function setEntityClass(string $entityClass): void
    {
        $this->entityClass = $entityClass;
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getLocaleFallbacks(): array
    {
        return $this->cacheProvider->get(static::CACHE_KEY, function () {
            return $this->getActualLocaleFallbacks();
        });
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

    public function clearCache(): void
    {
        $this->cacheProvider->delete(static::CACHE_KEY);
    }

    protected function getRootLocalizations(): array
    {
        /** @var LocalizationRepository $repository */
        $repository = $this->registry->getManagerForClass($this->entityClass)->getRepository($this->entityClass);

        return $repository->findRootsWithChildren();
    }

    protected function localizationToArray(Localization $localization): array
    {
        $children = [];
        foreach ($localization->getChildLocalizations() as $child) {
            $children = array_merge($children, $this->localizationToArray($child));
        }
        return [$localization->getLanguageCode() => $children];
    }

    public function warmUp($cacheDir): void
    {
        try {
            $this->clearCache();
            $this->getLocaleFallbacks();
        } catch (InvalidFieldNameException $exception) {
            // Cache warming can be used during upgrade from the app version where not all required columns yet exist.
            // Silently skips warming of locale fallbacks in this case, considering as not an error.
        }
    }

    public function isOptional(): bool
    {
        return true;
    }
}
