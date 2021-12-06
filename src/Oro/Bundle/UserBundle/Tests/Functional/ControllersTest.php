<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class ControllersTest extends WebTestCase
{
    /** @var Registry */
    private $registry;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->registry = $this->getContainer()->get('doctrine');
        $this->loadFixtures([LoadUserData::class]);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_user_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_user_create'));
        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_user_user_form[enabled]'] = 1;
        $form['oro_user_user_form[username]'] = 'testUser1';
        $form['oro_user_user_form[plainPassword][first]'] = 'password';
        $form['oro_user_user_form[plainPassword][second]'] = 'password';
        $form['oro_user_user_form[firstName]'] = 'First Name';
        $form['oro_user_user_form[lastName]'] = 'Last Name';
        $form['oro_user_user_form[birthday]'] = '2013-01-01';
        $form['oro_user_user_form[email]'] = 'test@test.com';
        //$form['oro_user_user_form[tags][owner]'] = 'tags1';
        //$form['oro_user_user_form[tags][all]'] = null;
        $form['oro_user_user_form[groups][0]']->tick();
        $form['oro_user_user_form[userRoles][0]']->tick();
        //$form['oro_user_user_form[values][company][varchar]'] = 'company';
        $form['oro_user_user_form[owner]'] = 1;
        $form['oro_user_user_form[inviteUser]'] = false;
        //$form['oro_user_user_form[values][gender][option]'] = 6;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('User saved', $crawler->html());
    }

    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'users-grid',
            ['users-grid[_filter][username][value]' => 'testUser1']
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_update', ['id' => $result['id']])
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_user_user_form[username]'] = 'testUser1';
        $form['oro_user_user_form[firstName]'] = 'First Name Updated';
        $form['oro_user_user_form[lastName]'] = 'Last Name Updated';
        $form['oro_user_user_form[birthday]'] = '2013-01-02';
        $form['oro_user_user_form[email]'] = 'test@test.com';
        $form['oro_user_user_form[groups][1]']->tick();
        $form['oro_user_user_form[userRoles][1]']->tick();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('User saved', $crawler->html());
    }

    public function testApiGen()
    {
        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_apigen', ['id' => $user->getId()]),
            [],
            []
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 200, false);

        $form = $crawler->selectButton('Generate key')->form();
        $this->client->submit($form);
        $response = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($response, 200);
        $data = self::jsonToArray($response->getContent());

        $userApi = self::getContainer()
            ->get('oro_user.manager')
            ->getApi($user, $user->getOrganization());

        $this->assertEquals($userApi->getApiKey(), $data['data']['apiKey']);
    }

    public function testViewProfile()
    {
        $this->client->request('GET', $this->getUrl('oro_user_profile_view'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('John Doe - View - Users - User Management - System', $result->getContent());
    }

    public function testUpdateProfile()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_user_profile_update'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString(
            'John Doe - Edit - Users - User Management - System',
            $this->client->getResponse()->getContent()
        );
        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_user_user_form[birthday]'] = '1999-01-01';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('User saved', $crawler->html());

        $crawler = $this->client->request('GET', $this->getUrl('oro_user_profile_update'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString(
            'John Doe - Edit - Users - User Management - System',
            $this->client->getResponse()->getContent()
        );
        $form = $crawler->selectButton('Save and Close')->form();
        $this->assertEquals('1999-01-01', $form['oro_user_user_form[birthday]']->getValue());
    }

    /**
     * @dataProvider autoCompleteHandlerProvider
     */
    public function testAutoCompleteHandler(bool $active, string $handlerName, string $query)
    {
        $user = $this->registry->getRepository(User::class)->findOneBy(['username' => 'simple_user']);
        $user->setEnabled($active);
        $this->registry->getManager()->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_form_autocomplete_search'),
            [
                'page' => 1,
                'per_page' => 10,
                'name' => $handlerName,
                'query' => $query,
            ]
        );

        $result = $this->client->getResponse();
        $arr = $this->getJsonResponseContent($result, 200);
        $this->assertCount((int)$active, $arr['results']);
    }

    public function autoCompleteHandlerProvider(): array
    {
        return [
                'Acl user autocomplete handler active' =>
                [
                    'active' => true,
                    'handler' => 'acl_users',
                    'query' => 'Elley Towards;Oro_Bundle_UserBundle_Entity_User;CREATE;0;'
                ],
                'Acl user autocomplete handler inactive' =>
                [
                    'active' => false,
                    'handler' => 'acl_users',
                    'query' => 'Elley Towards;Oro_Bundle_UserBundle_Entity_User;CREATE;0;'
                ],
                'Organization user autocomplete handler active' =>
                [
                    'active' => true,
                    'handler' => 'organization_users',
                    'query' => 'Elley Towards'
                ],
                'Organization user autocomplete handler inactive' =>
                [
                    'active' => false,
                    'handler' => 'organization_users',
                    'query' => 'Elley Towards'
                ],
        ];
    }
}
