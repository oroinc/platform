<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

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
}
