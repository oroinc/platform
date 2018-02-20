<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;

class LoadImapEmailData extends AbstractFixture implements DependentFixtureInterface
{
    const IMAP_EMAIL_1 = 'imap_email.1';
    const IMAP_EMAIL_2 = 'imap_email.2';
    const IMAP_EMAIL_3 = 'imap_email.3';
    const IMAP_EMAIL_4 = 'imap_email.4';
    const IMAP_EMAIL_5 = 'imap_email.5';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            self::IMAP_EMAIL_1 => [
                'email' => 'email_1',
                'imapFolder' => LoadImapEmailFolderData::INBOX_IMAP_EMAIL_FOLDER,
            ],
            self::IMAP_EMAIL_2 => [
                'email' => 'email_2',
                'imapFolder' => LoadImapEmailFolderData::INBOX_IMAP_EMAIL_FOLDER,
            ],
            self::IMAP_EMAIL_3 => [
                'email' => 'email_3',
                'imapFolder' => LoadImapEmailFolderData::INBOX_IMAP_EMAIL_FOLDER,
            ],
            self::IMAP_EMAIL_4 => [
                'email' => 'email_4',
                'imapFolder' => LoadImapEmailFolderData::OTHER_IMAP_EMAIL_FOLDER,
            ],
            self::IMAP_EMAIL_5 => [
                'email' => 'email_5',
                'imapFolder' => LoadImapEmailFolderData::OTHER_IMAP_EMAIL_FOLDER,
            ],
        ];

        foreach ($data as $referenceName => $itemData) {
            /** @var Email $email */
            $email = $this->getReference($itemData['email']);
            /** @var ImapEmailFolder $imapEmailFolder */
            $imapEmailFolder = $this->getReference($itemData['imapFolder']);

            $imapEmail = new ImapEmail();
            $imapEmail->setEmail($email);
            $imapEmail->setImapFolder($imapEmailFolder);
            $imapEmail->setUid(1);

            $manager->persist($imapEmail);

            $this->addReference($referenceName, $imapEmail);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadImapEmailFolderData::class];
    }
}
