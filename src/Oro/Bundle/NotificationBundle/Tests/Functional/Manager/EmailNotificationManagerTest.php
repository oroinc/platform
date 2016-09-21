<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional\Manager;

use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailNotificationManagerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_notifications.manager.email_notification');

        $this->assertInstanceOf(EmailNotificationManager::class, $service);
    }
}
