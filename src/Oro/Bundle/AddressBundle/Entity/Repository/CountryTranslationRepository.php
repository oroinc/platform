<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\TranslationBundle\Entity\Repository\AbstractTranslationRepository;

/**
 * Gedmo translation repository for Country dictionary.
 */
class CountryTranslationRepository extends AbstractTranslationRepository
{
    /**
     * {@inheritdoc}
     */
    public function updateTranslations(array $data, string $locale): void
    {
        $this->doUpdateTranslations(Country::class, 'name', $data, $locale);
    }

    public function getAllIdentities(): array
    {
        return $this->doGetAllIdentities(Country::class, 'iso2Code');
    }

    public function updateDefaultTranslations(array $data): void
    {
        $this->doUpdateDefaultTranslations(
            Country::class,
            'name',
            'iso2Code',
            'iso2_code',
            $data
        );
    }
}
