<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

/**
 * Interface for dictionary entity repositories which adds support of translating entities based on data from message
 * catalogue for the default locale.
 */
interface IdentityAwareTranslationRepositoryInterface
{
    /**
     * @return array
     */
    public function getAllIdentities();

    /**
     * @param array $data
     */
    public function updateTranslations(array $data);
}
