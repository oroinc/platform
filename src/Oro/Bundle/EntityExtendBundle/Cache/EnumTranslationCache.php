<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

/**
 * Cache for Enum values
 */
class EnumTranslationCache
{
    /**
     * @var Cache
     */
    protected $cache;
    
    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var LocaleSettings */
    private $localeSettings;

    /**
     * @param Cache $cache
     * @param LocalizationHelper $localizationHelper
     * @param LocaleSettings $localeSettings
     */
    public function __construct(
        Cache $cache,
        LocalizationHelper $localizationHelper,
        LocaleSettings $localeSettings
    ) {
        $this->cache = $cache;
        $this->localizationHelper = $localizationHelper;
        $this->localeSettings = $localeSettings;
    }

    /**
     * Check that cache contains values
     *
     * @param string $enumValueEntityClass
     *
     * @return bool
     */
    public function contains(string $enumValueEntityClass): bool
    {
        $key = $this->getKey($enumValueEntityClass);

        return $this->cache->contains($key);
    }

    /**
     * Fetch values from a cache
     *
     * @param string $enumValueEntityClass
     *
     * @return array
     *         key   => enum value entity class name
     *         value => array // values are sorted by priority
     *             key   => enum value id
     *             value => enum value name
     */
    public function fetch(string $enumValueEntityClass): array
    {
        $key = $this->getKey($enumValueEntityClass);
        $value = $this->cache->fetch($key);

        return false !== $value ? $value : [];
    }

    /**
     * Save values
     *
     * @param string $enumValueEntityClass
     * @param array $items
     *              key   => enum value entity class name
     *              value => array // values are sorted by priority
     *                  key   => enum value id
     *                  value => enum value name
     */
    public function save(string $enumValueEntityClass, array $items)
    {
        $this->cache->save($this->getKey($enumValueEntityClass), $items);
    }

    /**
     * Invalidate a cache by class of the enum value entity
     *
     * @param string $enumValueEntityClass
     */
    public function invalidate(string $enumValueEntityClass)
    {
        foreach ($this->localizationHelper->getLocalizations() as $localization) {
            $key = $this->getKey($enumValueEntityClass, $localization->getFormattingCode());
            $this->cache->delete($key);
        }
    }

    /**
     * @param string $enumValueEntityClass
     * @param string|null $locale
     *
     * @return string
     */
    private function getKey(string $enumValueEntityClass, string $locale = null): string
    {
        return sprintf('%s|%s', $enumValueEntityClass, $locale ?? $this->getLocaleKey());
    }

    /**
     * @return string
     */
    private function getLocaleKey(): string
    {
        return $this->localizationHelper->getCurrentLocalization()
            ? $this->localizationHelper->getCurrentLocalization()->getFormattingCode()
            : $this->localeSettings->getLocale();
    }
}
