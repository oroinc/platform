<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Form\Type;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mime\RawMessage;

class UserTypeTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const NAME_PREFIX = 'NamePrefix';
    private const MIDDLE_NAME = 'MiddleName';
    private const NAME_SUFFIX = 'NameSuffix';
    private const FIRST_NAME = 'John';
    private const LAST_NAME = 'Doe';

    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadUserData::SIMPLE_USER, LoadUserData::SIMPLE_USER_PASSWORD)
        );
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadUserData::class]);
    }

    public function createDataProvider(): array
    {
        return [
            'empty password and send password' => [
                'username' => 'test1',
                'email' => 'first@example.com',
                'plainPassword' => '',
                'sendPassword' => true,
            ],
            'with password and send password' => [
                'username' => 'test2',
                'email' => 'second@example.com',
                'plainPassword' => 'Qwerty!123%$',
                'sendPassword' => true,
            ],
            'empty password and not send password' => [
                'username' => 'test3',
                'email' => 'third@example.com',
                'plainPassword' => '',
                'sendPassword' => false,
            ],
            'with password and not send password' => [
                'username' => 'test4',
                'email' => 'fourth@example.com',
                'plainPassword' => 'Qwerty!123%$',
                'sendPassword' => false,
            ],
        ];
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(string $username, string $email, string $plainPassword, bool $sendPassword): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $configManager = self::getConfigManager();
        $configManager->set('oro_user.send_password_in_invitation_email', $sendPassword);
        $configManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('oro_user_create'));
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var Role $role */
        $role = $this->getUserRoleRepository()->findOneBy([]);
        self::assertNotNull($role);

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_user_user_form[enabled]'] = true;
        $form['oro_user_user_form[username]'] = $username;
        $form['oro_user_user_form[namePrefix]'] = self::NAME_PREFIX;
        $form['oro_user_user_form[firstName]'] = self::FIRST_NAME;
        $form['oro_user_user_form[middleName]'] = self::MIDDLE_NAME;
        $form['oro_user_user_form[lastName]'] = self::LAST_NAME;
        $form['oro_user_user_form[nameSuffix]'] = self::NAME_SUFFIX;
        $form['oro_user_user_form[birthday]'] = date('Y-m-d');
        $form['oro_user_user_form[email]'] = $email;
        $form['oro_user_user_form[plainPassword][first]'] = $plainPassword;
        $form['oro_user_user_form[plainPassword][second]'] = $plainPassword;
        $form['oro_user_user_form[passwordGenerate]'] = empty($plainPassword);
        $form['oro_user_user_form[inviteUser]'] = true;
        $form['oro_user_user_form[userRoles][0]']->tick();

        $this->client->submit($form);

        $emailMessages = self::getMailerMessages();

        self::assertCount(1, $emailMessages);
        $this->assertMessage(array_shift($emailMessages), $email, $plainPassword);

        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('User saved', $crawler->html());
    }

    public function testUserChangeUsernameToAnotherUserUsername(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_user_profile_update'));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_user_user_form[username]'] = 'admin';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('This value is already used', $crawler->html());
        self::assertStringNotContainsString('User saved', $crawler->html());

        /** @var User $expectedUser */
        $expectedUser = $this->getReference(LoadUserData::SIMPLE_USER);
        $actualUsername = self::getContainer()->get('security.token_storage')->getToken()->getUsername();

        self::assertEquals($expectedUser->getUsername(), $actualUsername);
    }

    private function assertMessage(RawMessage $symfonyEmail, string $email, string $plainPassword): void
    {
        self::assertInstanceOf(SymfonyEmail::class, $symfonyEmail);

        /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $email]);

        self::assertNotNull($user);
        self::assertEmailAddressContains($symfonyEmail, 'to', $email);

        $configManager = self::getConfigManager(null);
        self::assertEmailAddressContains(
            $symfonyEmail,
            'from',
            $configManager->get('oro_notification.email_notification_sender_email')
        );

        self::assertStringContainsString('Invite user', $symfonyEmail->getSubject());

        if ($configManager->get('oro_user.send_password_in_invitation_email')) {
            self::assertStringContainsString('Password:', $symfonyEmail->getHtmlBody());
            self::assertStringContainsString($plainPassword, $symfonyEmail->getHtmlBody());
        } else {
            self::assertNotNull($user->getConfirmationToken());
            self::assertStringContainsString($user->getConfirmationToken(), $symfonyEmail->getHtmlBody());
            self::assertStringNotContainsString('Password:', $symfonyEmail->getHtmlBody());
        }
    }

    private function getUserRepository(): UserRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(User::class);
    }

    private function getUserRoleRepository(): RoleRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Role::class);
    }
}
