<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;

class LoadAdminOwnerEmailData extends LoadEmailData
{
    /**
     * {@inheritdoc}
     */
    protected function getEmailOwner(ObjectManager $om)
    {
        return $om->getRepository(User::class)->findOneByUsername('admin');
    }
}
