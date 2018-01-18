<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\DomCrawler\Form;

class UserTypeTest extends WebTestCase
{
    const NAME_PREFIX = 'NamePrefix';
    const MIDDLE_NAME = 'MiddleName';
    const NAME_SUFFIX = 'NameSuffix';
    const FIRST_NAME = 'John';
    const LAST_NAME = 'Doe';

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadUserData::SIMPLE_USER, LoadUserData::SIMPLE_USER_PASSWORD)
        );
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadUserData::class
        ]);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'empty password and send password' => [
                'username' => 'test1',
                'email' => 'first@example.com',
                'plainPassword' => '',
                'sendPassword' => true
            ],
            'with password and send password' => [
                'username' => 'test2',
                'email' => 'second@example.com',
                'plainPassword' => 'Qwerty!123%$',
                'sendPassword' => true
            ],
            'empty password and not send password' => [
                'username' => 'test3',
                'email' => 'third@example.com',
                'plainPassword' => '',
                'sendPassword' => false
            ],
            'with password and not send password' => [
                'username' => 'test4',
                'email' => 'fourth@example.com',
                'plainPassword' => 'Qwerty!123%$',
                'sendPassword' => false
            ],
        ];
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $username
     * @param string $email
     * @param string $plainPassword
     * @param bool $sendPassword
     */
    public function testCreate($username, $email, $plainPassword, $sendPassword)
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $configManager = $this->getContainer()->get('oro_config.user');
        $configManager->set('oro_user.send_password_in_invitation_email', $sendPassword);
        $configManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('oro_user_create'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var Role $role */
        $role = $this->getUserRoleRepository()->findOneBy([]);
        $this->assertNotNull($role);

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
        $form['oro_user_user_form[roles][0]']->tick();

        $this->client->submit($form);

        /** @var \Swift_Plugins_MessageLogger $emailLogging */
        $emailLogger = $this->getContainer()->get('swiftmailer.plugin.messagelogger');
        $emailMessages = $emailLogger->getMessages();

        $this->assertCount(1, $emailMessages);
        $this->assertMessage(array_shift($emailMessages), $email, $plainPassword);

        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('User saved', $crawler->html());
    }

    public function testUserChangeUsernameToAnotherUserUsername()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_user_profile_update'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_user_user_form[username]'] = 'admin';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('This value is already used', $crawler->html());
        $this->assertNotContains('User saved', $crawler->html());

        /** @var User $expectedUser */
        $expectedUser = $this->getReference(LoadUserData::SIMPLE_USER);
        $actualUsername = $this->getContainer()->get('security.token_storage')->getToken()->getUsername();

        $this->assertEquals($expectedUser->getUsername(), $actualUsername);
    }

    /**
     * @param \Swift_Message $message
     * @param string $email
     * @param string $plainPassword
     */
    protected function assertMessage(\Swift_Message $message, $email, $plainPassword)
    {
        /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $email]);

        $this->assertNotNull($user);
        $this->assertEquals($email, key($message->getTo()));

        $configManager = $this->getContainer()->get('oro_config.manager');

        $this->assertEquals(
            $configManager->get('oro_notification.email_notification_sender_email'),
            key($message->getFrom())
        );

        $this->assertContains('Invite user', $message->getSubject());

        if ($configManager->get('oro_user.send_password_in_invitation_email')) {
            $this->assertContains('Password:', $message->getBody());
            $this->assertContains($plainPassword, $message->getBody());
        } else {
            $this->assertNotNull($user->getConfirmationToken());
            $this->assertContains($user->getConfirmationToken(), $message->getBody());
            $this->assertNotContains('Password:', $message->getBody());
        }
    }

    /**
     * @return ObjectRepository|RoleRepository
     */
    protected function getUserRepository()
    {
        $class = User::class;

        return $this->getManager($class)->getRepository($class);
    }

    /**
     * @return ObjectRepository|RoleRepository
     */
    protected function getUserRoleRepository()
    {
        $class = Role::class;
        
        return $this->getManager($class)->getRepository($class);
    }

    /**
     * @param string $class
     * @return ObjectManager
     */
    protected function getManager($class)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($class);
    }
}
