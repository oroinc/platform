<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Cache for Enum values
 */
class EnumTranslationCache
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var LocaleSettings
     */
    private $localeSettings;

    public function __construct(TranslatorInterface $translator, Cache $cache)
    {
        $this->translator = $translator;
        $this->cache = $cache;
    }

    public function getLocalizationHelper(): LocalizationHelper
    {
        if (!$this->localizationHelper) {
            throw new \LogicException('LocalizationHelper must not be null.');
        }

        return $this->localizationHelper;
    }

    public function setLocalizationHelper(LocalizationHelper $localizationHelper): void
    {
        $this->localizationHelper = $localizationHelper;
    }

    public function getLocaleSettings(): LocaleSettings
    {
        if (!$this->localeSettings) {
            throw new \LogicException('LocaleSettings must not be null.');
        }

        return $this->localeSettings;
    }

    public function setLocaleSettings(LocaleSettings $localeSettings): void
    {
        $this->localeSettings = $localeSettings;
    }

    /**
     * Check that cache contains values
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
     */
    public function invalidate(string $enumValueEntityClass)
    {
        foreach ($this->getLocalizationHelper()->getLocalizations() as $localization) {
            $key = $this->getKey($enumValueEntityClass, $localization->getFormattingCode());
            $this->cache->delete($key);
        }
    }

    private function getKey(string $enumValueEntityClass, string $locale = null): string
    {
        return sprintf('%s|%s', $enumValueEntityClass, $locale ?? $this->getLocaleKey());
    }

    private function getLocaleKey(): string
    {
        return $this->getLocalizationHelper()->getCurrentLocalization()
            ? $this->getLocalizationHelper()->getCurrentLocalization()->getFormattingCode()
            : $this->getLocaleSettings()->getLocale();
    }
}
