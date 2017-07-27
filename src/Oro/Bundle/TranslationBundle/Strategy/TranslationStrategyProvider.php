<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

use Oro\Bundle\LocaleBundle\Translation\Strategy\LocalizationFallbackStrategy;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationStrategyProvider
{
    /** @var TranslationStrategyInterface */
    protected $strategy;

    /** @var TranslationStrategyInterface[] */
    protected $strategies = [];

    /**
     * @param TranslationStrategyInterface $strategy
     */
    public function setStrategy(TranslationStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * @return TranslationStrategyInterface
     */
    public function getStrategy()
    {
        if (!$this->strategy) {
            foreach ($this->strategies as $strategy) {
                if ($strategy->isApplicable()) {
                    $this->strategy = $strategy;
                    break;
                }
            }
        }

        return $this->strategy;
    }

    /**
     * @param TranslationStrategyInterface $strategy
     */
    public function addStrategy(TranslationStrategyInterface $strategy)
    {
        $this->strategies[$strategy->getName()] = $strategy;
    }

    /**
     * @return TranslationStrategyInterface[]
     */
    public function getStrategies()
    {
        return $this->strategies;
    }

    /**
     * @param TranslationStrategyInterface $strategy
     * @param string $locale
     * @return array
     */
    public function getFallbackLocales(TranslationStrategyInterface $strategy, $locale)
    {
        $fallbackTree = $strategy->getLocaleFallbacks();

        $fallback = $this->findPathToLocale($fallbackTree, $locale);
        if (!$fallback) {
            return $locale !== Translator::DEFAULT_LOCALE ? [Translator::DEFAULT_LOCALE] : [];
        }

        // remove current locale
        foreach ($fallback as $key => $value) {
            if ($value === $locale) {
                unset($fallback[$key]);
            }
        }

        // set order from most specific to most common
        $fallback = array_reverse($fallback);

        if ($strategy->getName() === LocalizationFallbackStrategy::NAME) {
            $fallback = array_unique($fallback);
        }

        return $fallback;
    }

    /**
     * List of all fallback locales as plain array
     *
     * @param TranslationStrategyInterface $strategy
     * @return array
     */
    public function getAllFallbackLocales(TranslationStrategyInterface $strategy)
    {
        $fallbackTree = $strategy->getLocaleFallbacks();

        return array_values(array_unique($this->convertTreeToPlainArray($fallbackTree)));
    }

    /**
     * @param array $tree
     * @return array
     */
    private function convertTreeToPlainArray(array $tree)
    {
        $plainArray = array_keys($tree);

        foreach ($tree as $locale => $subTree) {
            $plainArray = array_merge($plainArray, $this->convertTreeToPlainArray($subTree));
        }

        return $plainArray;
    }

    /**
     * @param array $tree
     * @param string $searchedLocale
     * @return array
     */
    private function findPathToLocale(array $tree, $searchedLocale)
    {
        foreach ($tree as $locale => $subTree) {
            if ($locale === $searchedLocale) {
                return [$locale];
            } else {
                $path = $this->findPathToLocale($subTree, $searchedLocale);
                if ($path) {
                    array_unshift($path, $locale);
                    return $path;
                }
            }
        }

        return [];
    }
}
