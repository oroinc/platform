<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class LoadEmailOriginData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $inboxFolder = new EmailFolder();
        $inboxFolder->setType(FolderType::INBOX);
        $inboxFolder->setName('Inbox');
        $inboxFolder->setFullName('Inbox');
        $manager->persist($inboxFolder);
        $this->setReference('inbox_folder', $inboxFolder);

        $sentFolder = new EmailFolder();
        $sentFolder->setType(FolderType::SENT);
        $sentFolder->setName('Sent');
        $sentFolder->setFullName('Sent');
        $manager->persist($sentFolder);
        $this->setReference('sent_folder', $sentFolder);

        $origin = new InternalEmailOrigin();
        $origin->setName(InternalEmailOrigin::BAP . '_API');
        $origin->addFolder($inboxFolder);
        $origin->addFolder($sentFolder);
        $origin->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
        $origin->setOwner($this->getReference(LoadUser::USER));

        $manager->persist($origin);
        $manager->flush();
    }
}
