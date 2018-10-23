<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

interface TranslationRepositoryInterface
{
    /**
     * @param array $data
     * @param string $locale
     */
    public function updateTranslations(array $data, string $locale);
}
