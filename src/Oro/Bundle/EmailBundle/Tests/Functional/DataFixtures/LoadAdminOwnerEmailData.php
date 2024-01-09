<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

class LoadAdminOwnerEmailData extends LoadEmailData
{
    /**
     * {@inheritDoc}
     */
    protected function getEmailOwner(): User
    {
        return $this->getReference(LoadUser::USER);
    }
}
