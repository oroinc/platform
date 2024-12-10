<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolationPerTest
 */
class ControllersResetTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->client->followRedirects();
        $this->loadFixtures([LoadUserData::class]);
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
        self::assertStringContainsString('oro_set_password_form[password]', $content);

        $form = $crawler->selectButton('Save')->form();

        $form['oro_set_password_form[password]'] = $this->generateRandomString(8) . '1Q';

        $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('"triggerSuccess":true', $result->getContent());

        $user = $this->getContainer()->get('doctrine')->getRepository(User::class)->find($user->getId());

        $this->assertNotNull($user->getPasswordChangedAt());
        $newPassword = $user->getPassword();

        $this->assertNotEquals($oldPassword, $newPassword);
    }

    public function testRequestAction()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_reset_request', ['_widgetContainer' => 'dialog'])
        );
        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();

        self::assertStringContainsString('oro_user_password_request[username]', $content);
        self::assertStringContainsString('_token', $content);

        $form = $crawler->selectButton('Request')->form();
        $form['oro_user_password_request[username]'] = LoadUserData::SIMPLE_USER_EMAIL;

        $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString(
            'If there is a user account associated with simple_user@example.com',
            $result->getContent()
        );
    }

    public function testSendForcedResetEmailAction()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $this->assertEquals(UserManager::STATUS_ACTIVE, $user->getAuthStatus()->getId());

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_user_send_forced_password_reset_email',
                ['id' => $user->getId(), '_widgetContainer' => 'dialog']
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString('/user/send-forced-password-reset-email', $result->getContent());

        $form = $crawler->selectButton('Reset')->form();
        $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertEquals(200, $result->getStatusCode());

        $expectedResponse = '{"widget":{"trigger":[{"eventFunction":"execute","name":"refreshPage"}],"remove":true}}';
        self::assertStringContainsString($expectedResponse, $result->getContent());

        $user = $this->getContainer()->get('doctrine')->getRepository(User::class)->find($user->getId());
        $this->assertEquals(UserManager::STATUS_RESET, $user->getAuthStatus()->getId());
    }

    public function testMassPasswordResetAction()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        /** @var User $user2 */
        $user2 = $this->getReference('simple_user2');

        $ids = [$user->getId()];
        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_user_mass_password_reset',
                [
                    'id' => $user->getId(),
                    'gridName' => 'users-grid',
                    'actionName' => 'reset_password',
                    'values' => $ids
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $response = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertContainsEquals(
            [
                'successful' => true,
                'count' => 1,
            ],
            $response
        );

        $repo = $this->getContainer()->get('doctrine')->getRepository(User::class);
        $user = $repo->find($user->getId());
        $user2 = $repo->find($user2->getId());

        $this->assertEquals(UserManager::STATUS_RESET, $user->getAuthStatus()->getId());
        $this->assertEquals(UserManager::STATUS_ACTIVE, $user2->getAuthStatus()->getId());
    }

    public function testResetAction()
    {
        /** @var User $user */
        $user = $this->getReference('user_with_confirmation_token');
        $oldPassword = $user->getPassword();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_reset_reset', ['token' => LoadUserData::CONFIRMATION_TOKEN]),
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString('name="oro_user_reset_form[plainPassword][first]"', $result->getContent());
        self::assertStringContainsString('name="oro_user_reset_form[plainPassword][second]"', $result->getContent());

        $form = $crawler->selectButton('Reset')->form();

        $form['oro_user_reset_form[plainPassword][first]'] = 'new_password';
        $form['oro_user_reset_form[plainPassword][second]'] = 'wrong_password';

        // This is instead of submit($form) to be able to pass noHashNavigationHeader
        $crawler = $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            $this->generateNoHashNavigationHeader()
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString('The passwords must match.', $result->getContent());

        // @codingStandardsIgnoreStart
        $errorDiv = $crawler->filterXPath(
            "//*/form[contains(@class, 'form-signin--reset')]/*/div[contains(@class, 'control-group')][2][contains(@class, 'error')]"
        );
        // @codingStandardsIgnoreEnd

        $this->assertEquals(1, $errorDiv->count());

        $form = $crawler->selectButton('Reset')->form();

        $form['oro_user_reset_form[plainPassword][first]'] = 'new_password';
        $form['oro_user_reset_form[plainPassword][second]'] = 'new_password';

        $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString(
            'Your password was successfully reset. You may log in now.',
            $result->getContent()
        );

        $user = $this->getContainer()->get('doctrine')->getRepository(User::class)->find($user->getId());

        $newPassword = $user->getPassword();
        $this->assertNotEquals($oldPassword, $newPassword);
    }

    public function testResetActionWithEmptyFields()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_reset_reset', ['token' => LoadUserData::CONFIRMATION_TOKEN]),
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString('name="oro_user_reset_form[plainPassword][first]"', $result->getContent());
        self::assertStringContainsString('name="oro_user_reset_form[plainPassword][second]"', $result->getContent());

        $form = $crawler->selectButton('Reset')->form();

        $form['oro_user_reset_form[plainPassword][first]'] = '';
        $form['oro_user_reset_form[plainPassword][second]'] = '';

        // This is instead of submit($form) to be able to pass noHashNavigationHeader
        $crawler = $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            $this->generateNoHashNavigationHeader()
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString('This value should not be blank.', $result->getContent());

        // @codingStandardsIgnoreStarts
        $errorDiv = $crawler->filterXPath(
            "//*/form[contains(@class, 'form-signin--reset')]/*/div[contains(@class, 'control-group')][2][contains(@class, 'error')]"
        );
        // @codingStandardsIgnoreEnd

        $this->assertEquals(1, $errorDiv->count());
    }
}
