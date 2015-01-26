<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class ControllersResetTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
    }

    public function testSetPasswordAction()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_user_reset_set_password', ['id' => 1, '_widgetContainer' => 'dialog'])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testSendEmailAction()
    {
        $this->client->request('POST', $this->getUrl('oro_user_reset_send_email', []));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testSendEmailAsAdminAction()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_user_reset_send_email_as_admin',
                ['id' => 1, '_widgetContainer' => 'dialog']
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

//    public function testSendEmailAction()
//    {
//        /** @var Crawler $crawler */
//        $crawler = $this->client->request('GET', $this->getUrl('oro_user_role_create'));
//        /** @var Form $form */
//        $form = $crawler->selectButton('Save and Close')->form();
//
//        $form['oro_user_role_form[label]'] = 'testRole';
//
//        $this->client->followRedirects(true);
//        $crawler = $this->client->submit($form);
//
//        $result = $this->client->getResponse();
//        $this->assertHtmlResponseStatusCodeEquals($result, 200);
//        $this->assertContains("Role saved", $crawler->html());
//    }
//
//    public function testSendEmailAsAdminAction()
//    {
//        $response = $this->client->requestGrid(
//            'roles-grid',
//            array('roles-grid[_filter][label][value]' => 'testRole')
//        );
//
//        $result = $this->getJsonResponseContent($response, 200);
//        $result = reset($result['data']);
//
//        /** @var Crawler $crawler */
//        $crawler = $this->client->request(
//            'GET',
//            $this->getUrl('oro_user_role_update', array('id' => $result['id']))
//        );
//        /** @var Form $form */
//        $form = $crawler->selectButton('Save and Close')->form();
//
//        $form['oro_user_role_form[label]'] = 'testRoleUpdated';
//        $form['oro_user_role_form[appendUsers]'] = 1;
//
//        $this->client->followRedirects(true);
//        $crawler = $this->client->submit($form);
//
//        $result = $this->client->getResponse();
//        $this->assertHtmlResponseStatusCodeEquals($result, 200);
//        $this->assertContains("Role saved", $crawler->html());
//    }
//
//    public function testGridData()
//    {
//        $response = $this->client->requestGrid(
//            'roles-grid',
//            array('roles-grid[_filter][label][value]' => 'testRoleUpdated')
//        );
//
//        $result = $this->getJsonResponseContent($response, 200);
//        $result = reset($result['data']);
//
//        $response = $this->client->requestGrid(
//            'role-users-grid',
//            array(
//                'role-users-grid[_filter][has_role][value]' => 1,
//                'role-users-grid[role_id]' => $result['id']
//            )
//        );
//
//        $result = $this->getJsonResponseContent($response, 200);
//        $result = reset($result['data']);
//
//        $this->assertEquals(1, $result['id']);
//    }
}
