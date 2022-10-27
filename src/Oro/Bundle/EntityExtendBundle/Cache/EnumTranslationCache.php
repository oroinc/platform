<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Cache for Enum values
 */
class EnumTranslationCache
{
    private CacheInterface $cache;
    private LocalizationHelper $localizationHelper;
    private LocaleSettings $localeSettings;

    public function __construct(
        CacheInterface $cache,
        LocalizationHelper $localizationHelper,
        LocaleSettings $localeSettings
    ) {
        $this->cache = $cache;
        $this->localizationHelper = $localizationHelper;
        $this->localeSettings = $localeSettings;
    }

    public function get(string $enumValueEntityClass, EnumValueRepository $repository): array
    {
        return $this->cache->get($this->getKey($enumValueEntityClass), function () use ($repository) {
            $result = [];
            $values = $repository->getValues();
            foreach ($values as $enum) {
                $result[$enum->getId()] = $enum->getName();
            }
            return $result;
        });
    }

    /**
     * Invalidate a cache by class of the enum value entity
     */
    public function invalidate(string $enumValueEntityClass): void
    {
        foreach ($this->localizationHelper->getLocalizations() as $localization) {
            $key = $this->getKey($enumValueEntityClass, $localization->getFormattingCode());
            $this->cache->delete($key);
        }
    }

    private function getKey(string $enumValueEntityClass, string $locale = null): string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey(
            sprintf('%s|%s', $enumValueEntityClass, $locale ?? $this->getLocaleKey())
        );
    }

    private function getLocaleKey(): string
    {
        return $this->localizationHelper->getCurrentLocalization()
            ? $this->localizationHelper->getCurrentLocalization()->getFormattingCode()
            : $this->localeSettings->getLocale();
    }
}
