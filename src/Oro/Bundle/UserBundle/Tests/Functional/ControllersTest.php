<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class ControllersTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
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
        $form['oro_user_user_form[roles][0]']->tick();
        //$form['oro_user_user_form[values][company][varchar]'] = 'company';
        $form['oro_user_user_form[owner]'] = 1;
        $form['oro_user_user_form[inviteUser]'] = false;
        //$form['oro_user_user_form[values][gender][option]'] = 6;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("User saved", $crawler->html());
    }

    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'users-grid',
            array('users-grid[_filter][username][value]' => 'testUser1')
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_update', array('id' => $result['id']))
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_user_user_form[enabled]'] = 1;
        $form['oro_user_user_form[username]'] = 'testUser1';
        $form['oro_user_user_form[firstName]'] = 'First Name Updated';
        $form['oro_user_user_form[lastName]'] = 'Last Name Updated';
        $form['oro_user_user_form[birthday]'] = '2013-01-02';
        $form['oro_user_user_form[email]'] = 'test@test.com';
        $form['oro_user_user_form[groups][1]']->tick();
        $form['oro_user_user_form[roles][1]']->tick();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("User saved", $crawler->html());
    }

    public function testApiGen()
    {
        $response = $this->client->requestGrid(
            'users-grid',
            array('users-grid[_filter][username][value]' => 'testUser1')
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->client->request(
            'GET',
            $this->getUrl('oro_user_apigen', array('id' => $result['id'])),
            array(),
            array(),
            array('HTTP_X-Requested-With' => 'XMLHttpRequest')
        );

        /** @var User $user */
        $user = $this->client
            ->getContainer()
            ->get('doctrine')
            ->getRepository('OroUserBundle:User')
            ->findOneBy(array('id' => $result['id']));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200, false);

        //verify result
        $this->assertEquals($user->getApi()->getApiKey(), trim($result->getContent(), '"'));
    }

    public function testViewProfile()
    {
        $this->client->request('GET', $this->getUrl('oro_user_profile_view'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('John Doe - Users - User Management - System', $result->getContent());
    }

    public function testUpdateProfile()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_user_profile_update'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains(
            'John Doe - Edit - Users - User Management - System',
            $this->client->getResponse()->getContent()
        );
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_user_user_form[birthday]'] = '1999-01-01';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("User saved", $crawler->html());

        $crawler = $this->client->request('GET', $this->getUrl('oro_user_profile_update'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains(
            'John Doe - Edit - Users - User Management - System',
            $this->client->getResponse()->getContent()
        );
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $this->assertEquals('1999-01-01', $form['oro_user_user_form[birthday]']->getValue());
    }

    public function testAutoCompleteTwoPart()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_form_autocomplete_search'),
            array(
                'page' => 1,
                'per_page' => 10,
                'name' => 'acl_users',
                'query' => 'John Doe;Oro_Bundle_UserBundle_Entity_User;CREATE;0;',
            )
        );

        $result = $this->client->getResponse();
        $arr = $this->getJsonResponseContent($result, 200);
        $this->assertCount(1, $arr['results']);
    }
}
