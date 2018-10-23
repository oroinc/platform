<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

/**
 * Interface for translation dictionary entity repositories which adds support of translation entities based on data
 * from message catalogue for the given locale.
 */
interface TranslationRepositoryInterface
{
    /**
     * @param array $data
     * @param string $locale
     */
    public function updateTranslations(array $data, string $locale);
}
