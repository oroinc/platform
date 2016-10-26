<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional\Manager;

use Oro\Bundle\NotificationBundle\Manager\EmailNotificationSender;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailNotificationSenderTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_notification.manager.email_notification_sender');

        $this->assertInstanceOf(EmailNotificationSender::class, $service);
    }
}
