<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

class TranslationStrategyProvider
{
    /**
     * @var TranslationStrategyInterface
     */
    protected $strategy;

    /**
     * @param TranslationStrategyInterface $defaultStrategy
     */
    public function __construct(TranslationStrategyInterface $defaultStrategy)
    {
        $this->strategy = $defaultStrategy;
    }

    /**
     * @return TranslationStrategyInterface
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * @param TranslationStrategyInterface $strategy
     */
    public function setStrategy(TranslationStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
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
            return [];
        }

        // remove current locale
        foreach ($fallback as $key => $value) {
            if ($value === $locale) {
                unset($fallback[$key]);
            }
        }

        // set order from most specific to most common
        $fallback = array_reverse($fallback);

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

        return array_unique($this->convertTreeToPlainArray($fallbackTree));
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
