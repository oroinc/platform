<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\UserBundle\Entity\User;

class LoadEmailUserData extends AbstractFixture implements DependentFixtureInterface
{
    const EMAIL_USER_1 = 'imap_email_user.1';
    const EMAIL_USER_2 = 'imap_email_user.2';
    const EMAIL_USER_3 = 'imap_email_user.3';
    const EMAIL_USER_4 = 'imap_email_user.4';
    const EMAIL_USER_5 = 'imap_email_user.5';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            self::EMAIL_USER_1 => [
                'origin' => LoadUserEmailOriginData::USER_EMAIL_ORIGIN_1,
                'owner' => 'simple_user',
                'email' => 'email_1',
                'folders' => [
                    LoadImapEmailFolderData::INBOX_EMAIL_FOLDER,
                ],
            ],
            self::EMAIL_USER_2 => [
                'origin' => LoadUserEmailOriginData::USER_EMAIL_ORIGIN_1,
                'owner' => 'simple_user',
                'email' => 'email_2',
                'folders' => [
                    LoadImapEmailFolderData::INBOX_EMAIL_FOLDER,
                ],
            ],
            self::EMAIL_USER_3 => [
                'origin' => LoadUserEmailOriginData::USER_EMAIL_ORIGIN_1,
                'owner' => 'simple_user',
                'email' => 'email_3',
                'folders' => [
                    LoadImapEmailFolderData::INBOX_EMAIL_FOLDER,
                ],
            ],
            self::EMAIL_USER_4 => [
                'origin' => LoadUserEmailOriginData::USER_EMAIL_ORIGIN_3,
                'owner' => 'simple_user2',
                'email' => 'email_4',
                'folders' => [
                    LoadImapEmailFolderData::OTHER_EMAIL_FOLDER,
                ],
            ],
            self::EMAIL_USER_5 => [
                'origin' => LoadUserEmailOriginData::USER_EMAIL_ORIGIN_3,
                'owner' => 'simple_user2',
                'email' => 'email_5',
                'folders' => [
                    LoadImapEmailFolderData::OTHER_EMAIL_FOLDER,
                ],
            ],
        ];

        foreach ($data as $referenceName => $item) {
            /** @var User $owner */
            $owner = $this->getReference($item['owner']);
            /** @var EmailOrigin $origin */
            $origin = $this->getReference($item['origin']);
            /** @var Email $email */
            $email = $this->getReference($item['email']);

            $emailUser = new EmailUser();
            $emailUser->setOrigin($origin);
            $emailUser->setOwner($owner);
            $emailUser->setEmail($email);
            $emailUser->setReceivedAt(new \DateTime('now', new \DateTimeZone('UTC')));

            foreach ($item['folders'] as $folder) {
                /** @var EmailFolder $folder */
                $folder = $this->getReference($folder);
                $emailUser->addFolder($folder);
            }

            $manager->persist($emailUser);

            $this->setReference($referenceName, $emailUser);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserEmailOriginData::class, LoadImapEmailFolderData::class];
    }
}
