<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadTypedUserEmailOriginData extends LoadUserEmailOriginData
{
    public const USER_EMAIL_ORIGIN_GMAIL_1 = 'user_email_origin.gmail.1';
    public const USER_EMAIL_ORIGIN_GMAIL_2 = 'user_email_origin.gmail.2';
    public const USER_EMAIL_ORIGIN_GMAIL_3 = 'user_email_origin.gmail.3';

    public const USER_EMAIL_ORIGIN_MICROSOFT_1 = 'user_email_origin.microsoft.1';
    public const USER_EMAIL_ORIGIN_MICROSOFT_2 = 'user_email_origin.microsoft.2';
    public const USER_EMAIL_ORIGIN_MICROSOFT_3 = 'user_email_origin.microsoft.3';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $token = $this->generateToken();
        $data = [
            self::USER_EMAIL_ORIGIN_GMAIL_1 => [
                'mailboxName' => 'Test Mailbox Gmail 1',
                'owner' => 'simple_user',
                'accountType' => 'gmail'
            ],
            self::USER_EMAIL_ORIGIN_GMAIL_2 => [
                'mailboxName' => 'Test Mailbox Gmail 2',
                'owner' => 'simple_user',
                'accountType' => 'gmail',
                'accessToken' => $token
            ],
            self::USER_EMAIL_ORIGIN_GMAIL_3 => [
                'mailboxName' => 'Test Mailbox Gmail 3',
                'owner' => 'simple_user',
                'accountType' => 'gmail',
                'refreshToken' => $token
            ],
            self::USER_EMAIL_ORIGIN_MICROSOFT_1 => [
                'mailboxName' => 'Test Mailbox Microsoft 1',
                'owner' => 'simple_user2',
                'accountType' => 'microsoft'
            ],
            self::USER_EMAIL_ORIGIN_MICROSOFT_2 => [
                'mailboxName' => 'Test Mailbox Microsoft 2',
                'owner' => 'simple_user2',
                'accountType' => 'microsoft',
                'accessToken' => $token
            ],
            self::USER_EMAIL_ORIGIN_MICROSOFT_3 => [
                'mailboxName' => 'Test Mailbox Microsoft 3',
                'owner' => 'simple_user2',
                'accountType' => 'microsoft',
                'refreshToken' => $token
            ]
        ];

        foreach ($data as $referenceName => $item) {
            $userEmailOrigin = new UserEmailOrigin();
            $userEmailOrigin->setMailboxName($item['mailboxName']);
            $userEmailOrigin->setOwner($this->getReference($item['owner']));
            $userEmailOrigin->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            if (isset($item['accountType'])) {
                $userEmailOrigin->setAccountType($item['accountType']);
            }
            if (isset($item['accessToken'])) {
                $userEmailOrigin->setAccessToken($item['accessToken']);
            }
            if (isset($item['refreshToken'])) {
                $userEmailOrigin->setRefreshToken($item['refreshToken']);
            }
            $manager->persist($userEmailOrigin);
            $this->setReference($referenceName, $userEmailOrigin);
        }
        $manager->flush();
    }

    private function generateToken(int $length = 8192): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
