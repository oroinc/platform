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

//    public function testSetPasswordActionCorrectPost()
//    {
//        $this->client->request(
//            'POST',
//            $this->getUrl('oro_user_reset_set_password', ['id' => 1, '_widgetContainer' => 'dialog'])
//        );
//        $result = $this->client->getResponse();
//        $this->assertHtmlResponseStatusCodeEquals($result, 200);
//    }
//
//    public function testSetPasswordActionIncorrectPost()
//    {
//        $this->client->request(
//            'POST',
//            $this->getUrl('oro_user_reset_set_password', ['id' => 1, '_widgetContainer' => 'dialog'])
//        );
//        $result = $this->client->getResponse();
//        $this->assertHtmlResponseStatusCodeEquals($result, 200);
//    }

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

//    public function testSendEmailAsAdminActionPostCorrectForm()
//    {
//        $this->client->request(
//            'POST',
//            $this->getUrl(
//                'oro_user_reset_send_email_as_admin',
//                ['id' => 1, '_widgetContainer' => 'dialog']
//            )
//        );
//        $result = $this->client->getResponse();
//        $this->assertHtmlResponseStatusCodeEquals($result, 200);
//    }
//
//    public function testSendEmailAsAdminActionPostIncorrectForm()
//    {
//        $this->client->request(
//            'POST',
//            $this->getUrl(
//                'oro_user_reset_send_email_as_admin',
//                ['id' => 1, '_widgetContainer' => 'dialog']
//            )
//        );
//        $result = $this->client->getResponse();
//        $this->assertHtmlResponseStatusCodeEquals($result, 200);
//    }
}
