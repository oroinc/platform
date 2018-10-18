<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TranslationBundle\Entity\Repository\AbstractTranslationRepository;

/**
 * Gedmo translation repository for Region dictionary.
 */
class RegionTranslationRepository extends AbstractTranslationRepository
{
    /**
     * @param array $data
     * @param string $locale
     */
    public function updateTranslations(array $data, string $locale)
    {
        $this->doUpdateTranslations(Region::class, 'name', $data, $locale);
    }
}
