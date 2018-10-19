<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

class LoadAdminOwnerEmailData extends LoadEmailData
{
    /**
     * {@inheritdoc}
     */
    protected function getEmailOwner(ObjectManager $om)
    {
        return $om->getRepository('OroUserBundle:User')->findOneByUsername('admin');
    }
}
