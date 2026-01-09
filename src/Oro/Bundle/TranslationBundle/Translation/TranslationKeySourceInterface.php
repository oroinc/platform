<?php

namespace Oro\Bundle\TranslationBundle\Translation;

/**
 * Defines the contract for translation key sources.
 *
 * Translation key sources provide the template and data needed to generate translation keys.
 * Implementations supply a template string that defines the key structure and data values
 * that are substituted into the template to create the final translation key.
 */
interface TranslationKeySourceInterface
{
    /**
     * @return string
     */
    public function getTemplate();

    /**
     * @return array
     */
    public function getData();
}
