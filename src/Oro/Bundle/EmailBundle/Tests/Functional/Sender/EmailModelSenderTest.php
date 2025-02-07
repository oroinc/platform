<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Sender;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolationPerTest
 */
class EmailModelSenderTest extends WebTestCase
{
    private EmailModelSender $emailModelSender;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateApiAuthHeader());
        $this->loadFixtures([LoadUserData::class]);

        $this->emailModelSender = $this->getContainer()->get('oro_email.sender.email_model_sender');
    }

    public function testSendEmailWithDefaultOwner(): void
    {
        $owner = $this->getDefaultUser();

        /** @var EmailUserRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository(EmailUser::class);
        $this->emailModelSender->send(self::getEmail());

        $email = $repository->findOneBy(['owner' => $owner, 'organization' => $owner->getOrganization()]);

        self::assertNotNull($owner, $email);
        self::assertSame($owner, $email->getOwner());
        self::assertSame($owner->getOrganization(), $email->getOwner()->getOrganization());
    }

    public function testSendEmailWithACLOwner(): void
    {
        $owner = self::setSecurityToken();
        $default = $this->getDefaultUser();

        /** @var EmailUserRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository(EmailUser::class);
        $this->emailModelSender->send(self::getEmail());

        $email = $repository->findOneBy(['owner' => $owner, 'organization' => $owner->getOrganization()]);

        self::assertNotNull($owner, $email);
        self::assertSame($owner, $email->getOwner());
        self::assertSame($owner->getOrganization(), $email->getOwner()->getOrganization());
        self::assertNull($repository->findOneBy(['owner' => $default, 'organization' => $default->getOrganization()]));
    }

    private function getEmail(): Email
    {
        $email = new Email();

        $email->setFrom('chris@example.com');
        $email->setTo(['alice@example.com', 'bob@example.com']);
        $email->setCc(['dave@example.com', 'eric@example.com']);
        $email->setBcc(['ryan@example.com', 'jonathan@example.com']);
        $email->setSubject('Email Subject');
        $email->setBody('Email Body');

        return $email;
    }

    private function getDefaultUser(): ?User
    {
        /** @var DefaultUserProvider $defaultUserProvider */
        $defaultUserProvider = self::getContainer()->get('oro_user.provider.default_user');

        return $defaultUserProvider->getDefaultUser('oro_email.default_email_owner');
    }

    private function setSecurityToken(): User
    {
        $container = $this->getContainer();

        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $token = new OrganizationToken($user->getOrganization(), ['ROLE_ADMINISTRATOR']);
        $token->setUser($user);

        $container
            ->get('security.token_storage')
            ->setToken($token);

        return $user;
    }
}
