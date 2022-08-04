<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * Provides a way to manage the current translation strategy.
 */
class TranslationStrategyProvider
{
    /** @var iterable|TranslationStrategyInterface[] */
    private $strategies;

    /** @var TranslationStrategyInterface|null */
    private $strategy;

    /**
     * @param iterable|TranslationStrategyInterface[] $strategies
     */
    public function __construct(iterable $strategies)
    {
        $this->strategies = $strategies;
    }

    public function setStrategy(TranslationStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * @return TranslationStrategyInterface
     */
    public function getStrategy()
    {
        if (null === $this->strategy) {
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
     * @return TranslationStrategyInterface[]
     */
    public function getStrategies()
    {
        $result = [];
        foreach ($this->strategies as $strategy) {
            $result[$strategy->getName()] = $strategy;
        }

        return $result;
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

        return array_unique(array_reverse($fallback));
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
