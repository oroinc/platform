<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;

class UpdateOriginName extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $repo = $manager->getRepository('OroEmailBundle:InternalEmailOrigin');

        $origins = $repo->findAll();
        if ($origins) {
            foreach ($origins as $origin) {
                $origin->setMailboxName(InternalEmailOrigin::MAILBOX_NAME);
            }

            $manager->flush();
        }
    }
}
