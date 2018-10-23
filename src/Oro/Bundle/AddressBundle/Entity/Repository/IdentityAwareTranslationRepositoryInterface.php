<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

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
