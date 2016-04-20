<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

interface TranslationStrategyInterface
{
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
     * Get string representation of current locale
     *
     * @return string
     */
    public function getCurrentLocale();
}
