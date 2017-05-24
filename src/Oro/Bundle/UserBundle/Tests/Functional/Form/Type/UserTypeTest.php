<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Form\Type;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\DomCrawler\Form;

class UserTypeTest extends WebTestCase
{
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
        $this->assertContains("This value is already used", $crawler->html());
        $this->assertNotContains("User saved", $crawler->html());

        /** @var User $expectedUser */
        $expectedUser = $this->getReference(LoadUserData::SIMPLE_USER);
        $actualUsername = $this->getContainer()->get('security.token_storage')->getToken()->getUsername();

        $this->assertEquals($expectedUser->getUsername(), $actualUsername);
    }
}
