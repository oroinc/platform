<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TranslationBundle\Entity\Repository\AbstractTranslationRepository;

/**
 * Gedmo translation repository for Region dictionary.
 */
class RegionTranslationRepository extends AbstractTranslationRepository
{
    #[\Override]
    public function updateTranslations(array $data, string $locale): void
    {
        $this->doUpdateTranslations(Region::class, 'name', $data, $locale);
    }

    #[\Override]
    public function updateDefaultTranslations(array $data): void
    {
        $this->doUpdateDefaultTranslations(
            Region::class,
            'name',
            'combinedCode',
            'combined_code',
            $data
        );
    }
}
