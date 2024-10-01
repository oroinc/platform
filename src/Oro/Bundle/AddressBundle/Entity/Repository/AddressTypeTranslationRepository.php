<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\TranslationBundle\Entity\Repository\AbstractTranslationRepository;

/**
 * Gedmo translation repository for AddressType dictionary.
 */
class AddressTypeTranslationRepository extends AbstractTranslationRepository
{
    #[\Override]
    public function updateTranslations(array $data, string $locale): void
    {
        $this->doUpdateTranslations(AddressType::class, 'label', $data, $locale);
    }

    #[\Override]
    public function getAllIdentities(): array
    {
        return $this->doGetAllIdentities(AddressType::class, 'name');
    }

    #[\Override]
    public function updateDefaultTranslations(array $data): void
    {
        $this->doUpdateDefaultTranslations(
            AddressType::class,
            'label',
            'name',
            'name',
            $data
        );
    }
}
