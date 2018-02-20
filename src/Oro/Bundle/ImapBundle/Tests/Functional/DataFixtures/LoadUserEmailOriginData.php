<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\UserBundle\Entity\User;

class LoadUserEmailOriginData extends AbstractFixture implements DependentFixtureInterface
{
    const USER_EMAIL_ORIGIN_1 = 'user_email_origin.1';
    const USER_EMAIL_ORIGIN_2 = 'user_email_origin.2';
    const USER_EMAIL_ORIGIN_3 = 'user_email_origin.3';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
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

        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        foreach ($data as $referenceName => $item) {
            /** @var User $owner */
            $owner = $this->getReference($item['owner']);

            $userEmailOrigin = new UserEmailOrigin();
            $userEmailOrigin->setMailboxName($item['mailboxName']);
            $userEmailOrigin->setOwner($owner);
            $userEmailOrigin->setOrganization($organization);

            $manager->persist($userEmailOrigin);

            $this->setReference($referenceName, $userEmailOrigin);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadEmailData::class];
    }
}
