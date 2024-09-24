<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadUserEmailOriginData extends AbstractFixture implements DependentFixtureInterface
{
    public const USER_EMAIL_ORIGIN_1 = 'user_email_origin.1';
    public const USER_EMAIL_ORIGIN_2 = 'user_email_origin.2';
    public const USER_EMAIL_ORIGIN_3 = 'user_email_origin.3';

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadEmailData::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $data = [
            self::USER_EMAIL_ORIGIN_1 => [
                'mailboxName' => 'Test Mailbox',
                'owner' => 'simple_user',
            ],
            self::USER_EMAIL_ORIGIN_2 => [
                'mailboxName' => 'Test Mailbox 2',
                'owner' => 'simple_user'
            ],
            self::USER_EMAIL_ORIGIN_3 => [
                'mailboxName' => 'Test Mailbox 3',
                'owner' => 'simple_user2'
            ],
        ];

        foreach ($data as $referenceName => $item) {
            $userEmailOrigin = new UserEmailOrigin();
            $userEmailOrigin->setMailboxName($item['mailboxName']);
            $userEmailOrigin->setOwner($this->getReference($item['owner']));
            $userEmailOrigin->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $manager->persist($userEmailOrigin);
            $this->setReference($referenceName, $userEmailOrigin);
        }
        $manager->flush();
    }
}
