<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class ControllersResetTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->client->followRedirects();
        $this->loadFixtures([
            'Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData',
            'Oro\Bundle\TestFrameworkBundle\Fixtures\LoadUserData',
        ]);
    }

    public function testSetPasswordAction()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $oldPassword = $user->getPassword();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_reset_set_password', ['id' => $user->getId(), '_widgetContainer' => 'dialog'])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertContains('oro.user.suggest_password.label', $content);
        $this->assertContains('oro_set_password_form[password]', $content);

        $form = $crawler->selectButton('Save')->form();

        $form['oro_set_password_form[password]'] = $this->generateRandomString(8);

        $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('oro.user.change_password.flash.success', $result->getContent());

        $user = $this->getContainer()->get('doctrine')->getRepository('OroUserBundle:User')->find($user->getId());

        $this->assertNotNull($user->getPasswordChangedAt());
        $newPassword = $user->getPassword();
        $this->assertNotEquals($oldPassword, $newPassword);
    }

    public function testSendEmailAction()
    {
        /** @var User $user */
        $user = $this->getReference('default_user');

        $this->client->request(
            'POST',
            $this->getUrl('oro_user_reset_send_email'),
            ['username' => $user->getUsername()],
            [],
            $this->generateNoHashNavigationHeader()
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('An email has been sent to', $result->getContent());

        $user = $this->getContainer()->get('doctrine')->getRepository('OroUserBundle:User')->find($user->getId());
        $this->assertNotNull($user->getPasswordRequestedAt());
    }

    public function testSendEmailAsAdminAction()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_user_reset_send_email_as_admin',
                ['id' => $user->getId(), '_widgetContainer' => 'dialog']
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Are you sure you want to proceed?', $result->getContent());

        $form = $crawler->selectButton('Reset')->form();
        $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertContains('oro.user.reset_password.flash.success', $result->getContent());

        $user = $this->getContainer()->get('doctrine')->getRepository('OroUserBundle:User')->find($user->getId());
        $this->assertNotNull($user->getPasswordRequestedAt());
    }
}
