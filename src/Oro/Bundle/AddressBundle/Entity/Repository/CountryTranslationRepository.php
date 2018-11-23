<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\TranslationBundle\Entity\Repository\AbstractTranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepositoryInterface;

/**
 * Gedmo translation repository for Country dictionary.
 */
class CountryTranslationRepository extends AbstractTranslationRepository implements TranslationRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function updateTranslations(array $data, string $locale)
    {
        $this->doUpdateTranslations(Country::class, 'name', $data, $locale);
    }
}
