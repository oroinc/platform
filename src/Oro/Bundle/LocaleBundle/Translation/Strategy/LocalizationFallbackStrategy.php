<?php

namespace Oro\Bundle\LocaleBundle\Translation\Strategy;

use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Provides a tree of locale fallbacks configured by a user.
 */
class LocalizationFallbackStrategy implements TranslationStrategyInterface, CacheWarmerInterface
{
    private const CACHE_KEY = 'localization_fallbacks';

    private ManagerRegistry $doctrine;
    private CacheInterface $cacheProvider;

    public function __construct(ManagerRegistry $doctrine, CacheInterface $cacheProvider)
    {
        $this->doctrine = $doctrine;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'oro_localization_fallback_strategy';
    }

    /**
     * {@inheritDoc}
     */
    public function getLocaleFallbacks(): array
    {
        return $this->cacheProvider->get(self::CACHE_KEY, function () {
            return $this->getActualLocaleFallbacks();
        });
    }

    public function clearCache(): void
    {
        $this->cacheProvider->delete(self::CACHE_KEY);
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    public function isOptional(): bool
    {
        return true;
    }

    private function getActualLocaleFallbacks(): array
    {
        /** All localizations always should have only one parent that equals to default language */
        return [
            Configuration::DEFAULT_LOCALE => $this->getLocalizationThree()
        ];
    }

    private function getLocalizationThree(): array
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Localization::class);
        $rows = $em->createQueryBuilder()
            ->select('loc.id, IDENTITY(loc.parentLocalization) AS parentId, lang.code AS langCode')
            ->from(Localization::class, 'loc')
            ->innerJoin('loc.language', 'lang')
            ->getQuery()
            ->getArrayResult();

        return $this->buildLocalizationThree($rows);
    }

    private function buildLocalizationThree(array $rows, ?int $parentLocalizationId = null): array
    {
        $result = [];
        foreach ($rows as $row) {
            $parentId = isset($row['parentId']) ? (int)$row['parentId'] : null;
            if ($parentLocalizationId === $parentId) {
                $result[$row['langCode']] = $this->buildLocalizationThree($rows, (int)$row['id']);
            }
        }

        return $result;
    }
}
