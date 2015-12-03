<?php

namespace Oro\Bundle\EmailBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;

class LoadInternalEmailOrigins extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $outboxFolder = new EmailFolder();
        $outboxFolder
            ->setType(EmailFolder::SENT)
            ->setName(EmailFolder::SENT)
            ->setFullName(EmailFolder::SENT);

        $origin = new InternalEmailOrigin();
        $origin
            ->setName(InternalEmailOrigin::BAP)
            ->addFolder($outboxFolder);

        $manager->persist($origin);
        $manager->flush();
    }
}
