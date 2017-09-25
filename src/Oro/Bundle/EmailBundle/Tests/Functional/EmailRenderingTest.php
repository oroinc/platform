<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class EmailRenderingTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadBadEmailData']);
    }

    public function testMyEmailsList()
    {
        $url = $this->getUrl('oro_email_user_emails');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertNotContainsMaliciousScripts($content);
    }

    public function testMyEmailsWidget()
    {
        $url = $this->getUrl(
            'oro_email_dashboard_recent_emails',
            [
                'widget' => 'widget_id',
                'activeTab' => 'unread',
            ]
        );
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertNotContainsMaliciousScripts($content);
    }

    private function assertNotContainsMaliciousScripts($content)
    {
        $this->assertNotContains('<script>alert("', $content);
        $this->assertNotContains('<Script>alert("', $content);
        $this->assertNotContains('&lt;script&gt;', $content);
        $this->assertNotContains('&lt;Script&gt;', $content);
    }
}
