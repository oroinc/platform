<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class MassControllersTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            array(),
            array_merge($this->generateBasicAuthHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            ['Oro\Bundle\NotificationBundle\Tests\Functional\Fixture\LoadMassNotificationFixtures']
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
        $this->assertContains($notification->getBody(), $result->getContent());
    }
}
