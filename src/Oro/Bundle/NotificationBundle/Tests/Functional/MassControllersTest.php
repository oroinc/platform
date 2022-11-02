<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MassControllersTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            array(),
            $this->generateBasicAuthHeader()
        );
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            ['Oro\Bundle\NotificationBundle\Tests\Functional\DataFixtures\LoadMassNotificationFixtures']
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_notification_massnotification_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testView()
    {
        $notification = $this->getReference('mass_notification');
        $this->client->request(
            'GET',
            $this->getUrl('oro_notification_massnotification_view', array('id' => $notification->getId()))
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString($notification->getBody(), $result->getContent());
    }
}
