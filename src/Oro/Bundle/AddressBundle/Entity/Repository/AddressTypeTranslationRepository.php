<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\TranslationBundle\Entity\Repository\AbstractTranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepositoryInterface;

/**
 * Gedmo translation repository for AddressType dictionary.
 */
class AddressTypeTranslationRepository extends AbstractTranslationRepository implements TranslationRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function updateTranslations(array $data, string $locale)
    {
        $this->doUpdateTranslations(AddressType::class, 'label', $data, $locale);
    }
}
