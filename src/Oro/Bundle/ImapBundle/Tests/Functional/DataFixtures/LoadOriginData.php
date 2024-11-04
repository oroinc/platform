<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadOriginData extends AbstractFixture implements DependentFixtureInterface
{
    private array $data = [
        'origin_one' => [
            'mailboxName' => 'Test Mailbox',
            'owner' => LoadUserData::SIMPLE_USER_ENABLED
        ],
        'origin_two' => [
            'mailboxName' => 'Test Mailbox 2',
            'folder' => [
                'name' => 'Folder 1',
                'type' => 'inbox',
                'enabled' => true,
            ],
            'owner' => LoadUserData::SIMPLE_USER_ENABLED
        ],
        'origin_tree' => [
            'mailboxName' => 'Test Mailbox 3',
            'owner' => LoadUserData::SIMPLE_USER_DISABLED
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUserData::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $reference => $data) {
            $origin = new UserEmailOrigin();
            $origin->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $origin->setOwner($this->getReference($data['owner']));
            $origin->setMailboxName($data['mailboxName']);
            $this->setReference($reference, $origin);
            $manager->persist($origin);
            if (\array_key_exists('folder', $data)) {
                $folder = new EmailFolder();
                $folder->setOrigin($origin);
                $folder->setSyncEnabled($data['folder']['enabled']);
                $folder->setFullName($data['folder']['name']);
                $folder->setName($data['folder']['name']);
                $folder->setType($data['folder']['type']);
                $manager->persist($folder);
                $imapFolder = new ImapEmailFolder();
                $imapFolder->setFolder($folder);
                $imapFolder->setUidValidity(1);
                $manager->persist($imapFolder);
                $origin->addFolder($folder);
            }
        }
        $manager->flush();
    }
}
