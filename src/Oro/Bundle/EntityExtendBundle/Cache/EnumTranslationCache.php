<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Cache for Enum options.
 */
class EnumTranslationCache
{
    public function __construct(
        private CacheInterface $cache,
        private LocalizationHelper $localizationHelper,
        private LocaleSettings $localeSettings,
        private TranslatorInterface $translator
    ) {
    }

    public function get(string $enumCode, EnumOptionRepository $repository): array
    {
        return $this->cache->get($this->getKey($enumCode), function () use ($repository, $enumCode) {
            $result = [];
            $values = $repository->getValues($enumCode);
            foreach ($values as $enum) {
                $result[$enum->getId()] = $this->translator->trans(
                    ExtendHelper::buildEnumOptionTranslationKey($enum->getId())
                );
            }
            return $result;
        });
    }

    /**
     * Invalidate a cache by enum_code of the enum option entity
     */
    public function invalidate(string $enumCode): void
    {
        foreach ($this->localizationHelper->getLocalizations() as $localization) {
            $key = $this->getKey($enumCode, $localization->getFormattingCode());
            $this->cache->delete($key);
        }
    }

    private function getKey(string $enumCode, ?string $locale = null): string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey(
            sprintf('%s|%s', $enumCode, $locale ?? $this->getLocaleKey())
        );
    }

    private function getLocaleKey(): string
    {
        return $this->localizationHelper->getCurrentLocalization()
            ? $this->localizationHelper->getCurrentLocalization()->getFormattingCode()
            : $this->localeSettings->getLocale();
    }
}
