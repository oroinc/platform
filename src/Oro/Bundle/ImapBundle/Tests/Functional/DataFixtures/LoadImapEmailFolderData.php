<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

class LoadImapEmailFolderData extends AbstractFixture implements DependentFixtureInterface
{
    const INBOX_EMAIL_FOLDER = 'email_folder.inbox';
    const SENT_EMAIL_FOLDER = 'email_folder.sent';
    const OTHER_EMAIL_FOLDER = 'email_folder.other';

    const INBOX_IMAP_EMAIL_FOLDER = 'imap_email_folder.inbox';
    const SENT_IMAP_EMAIL_FOLDER = 'imap_email_folder.sent';
    const OTHER_IMAP_EMAIL_FOLDER = 'imap_email_folder.other';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadEmailFolders($manager);
        $this->loadImapEmailFolders($manager);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadEmailFolders(ObjectManager $manager)
    {
        $data = [
            self::INBOX_EMAIL_FOLDER => [
                'origin' => LoadUserEmailOriginData::USER_EMAIL_ORIGIN_1,
                'fullName' => 'Folder Inbox',
                'name' => 'Folder Inbox',
                'type' => FolderType::INBOX,
                'syncEnabled' => true,
            ],
            self::SENT_EMAIL_FOLDER => [
                'origin' => LoadUserEmailOriginData::USER_EMAIL_ORIGIN_1,
                'fullName' => 'Folder Sent',
                'name' => 'Folder Sent',
                'type' => FolderType::SENT,
                'syncEnabled' => true,
            ],
            self::OTHER_EMAIL_FOLDER => [
                'origin' => LoadUserEmailOriginData::USER_EMAIL_ORIGIN_3,
                'fullName' => 'Folder Other',
                'name' => 'Folder Other',
                'type' => FolderType::OTHER,
                'syncEnabled' => false,
            ],
        ];

        foreach ($data as $referenceName => $itemData) {
            $this->addEmailFolder($manager, $referenceName, $itemData);
        }
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadImapEmailFolders(ObjectManager $manager)
    {
        $data = [
            self::INBOX_IMAP_EMAIL_FOLDER => [
                'folder' => self::INBOX_EMAIL_FOLDER,
                'uidValidity' => 1,
            ],
            self::SENT_IMAP_EMAIL_FOLDER => [
                'folder' => self::SENT_EMAIL_FOLDER,
                'uidValidity' => 1,
            ],
            self::OTHER_IMAP_EMAIL_FOLDER => [
                'folder' => self::OTHER_EMAIL_FOLDER,
                'uidValidity' => 1,
            ],
        ];

        foreach ($data as $referenceName => $itemData) {
            /** @var EmailFolder $folder */
            $folder = $this->getReference($itemData['folder']);

            $imapEmailFolder = new ImapEmailFolder();
            $imapEmailFolder->setFolder($folder);
            $imapEmailFolder->setUidValidity($itemData['uidValidity']);

            $manager->persist($imapEmailFolder);

            $this->addReference($referenceName, $imapEmailFolder);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param string $referenceName
     * @param array $data
     */
    private function addEmailFolder(ObjectManager $manager, $referenceName, array $data)
    {
        /** @var UserEmailOrigin $origin */
        $origin = $this->getReference($data['origin']);

        $emailFolder = new EmailFolder();
        $emailFolder->setOrigin($origin);
        $emailFolder->setFullName($data['fullName']);
        $emailFolder->setName($data['name']);
        $emailFolder->setType($data['type']);
        $emailFolder->setSyncEnabled($data['syncEnabled']);

        $manager->persist($emailFolder);

        $this->addReference($referenceName, $emailFolder);
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserEmailOriginData::class];
    }
}
