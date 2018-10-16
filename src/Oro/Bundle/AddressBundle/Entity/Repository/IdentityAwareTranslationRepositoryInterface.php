<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepositoryInterface;

interface IdentityAwareTranslationRepositoryInterface extends TranslationRepositoryInterface
{
    /**
     * @return array
     */
    public function getAllIdentities();
}
