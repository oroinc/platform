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

    public function testCreateViewForm()
    {
        $url = $this->getUrl('oro_email_email_create', ['_widgetContainer' => 'dialog']);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertContains('From', $content);
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
        $url = $this->getUrl('oro_email_widget_emails', ['_widgetContainer' => 'dialog']);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testBaseEmails()
    {
        $url = $this->getUrl('oro_email_widget_base_emails', ['_widgetContainer' => 'dialog']);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testUserEmails()
    {
        $url = $this->getUrl('oro_email_user_emails', ['_widgetContainer' => 'dialog']);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }
}
