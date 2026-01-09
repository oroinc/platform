<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

/**
 * Defines the contract for translation strategies.
 *
 * Translation strategies determine how locales are organized and how fallback chains work
 * when a translation is not available in the requested locale. Implementations provide
 * a unique identifier, a tree of locale fallbacks, and indicate whether the strategy
 * is applicable in the current system configuration.
 */
interface TranslationStrategyInterface
{
    /**
     * Unique text identifier of the strategy
     *
     * @return string
     */
    public function getName();

    /**
     * Get tree of locale fallbacks
     * Example of result:
     * [
     *      'en' => [
     *          'en_US' => [ ... ],
     *          'en_CA' => [],
     *          'en_GB  => [],
     *          ...
     *      ],
     *      ...
     * ]
     *
     * @return array
     */
    public function getLocaleFallbacks();

    /**
     * @return bool
     */
    public function isApplicable();
}
