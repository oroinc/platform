<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData']);
    }

    public function testView()
    {
        $url = $this->getUrl('oro_email_view', ['id' => $this->getReference('email_1')->getId()]);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertContains('My Web Store Introduction', $content);
        $this->assertContains('Thank you for signing up to My Web Store!', $content);
    }

    public function testCreate()
    {
        $this->markTestIncomplete('Skipped. Incomplete');

        $url = $this->getUrl('oro_email_email_create');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
//        $content = $result->getContent();
//        $this->assertContains('', $content);
    }

    public function testBody()
    {
        $url = $this->getUrl('oro_email_body', ['id' => $this->getReference('emailBody_1')->getId()]);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertContains('Thank you for signing up to My Web Store!', $content);
    }

    public function testActivity()
    {
        $this->markTestIncomplete('Skipped. Need activity fixture');

        $url = $this->getUrl('oro_email_activity_view', [
            'entityClass' => 'test',
            'entityId' => 1
        ]);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testAttachment()
    {
        $this->markTestIncomplete('Skipped. Need attachment fixture');

        $url = $this->getUrl('oro_email_attachment', [
            'id' => 1
        ]);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testEmails()
    {
        $this->markTestIncomplete('Skipped. Incomplete');

        $url = $this->getUrl('oro_email_widget_emails');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
//        $content = $result->getContent();
//        $this->assertContains('', $content);
    }

    public function testBaseEmails()
    {
        $this->markTestIncomplete('Skipped. Incomplete');

        $url = $this->getUrl('oro_email_widget_base_emails', [
            'id' => 1
        ]);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testUserEmails()
    {
        $this->markTestIncomplete('Skipped. Incomplete');

        $url = $this->getUrl('oro_email_user_emails', [
            'id' => 1
        ]);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }
}
