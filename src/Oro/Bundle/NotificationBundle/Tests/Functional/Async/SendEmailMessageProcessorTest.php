<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional\Async;

use Oro\Bundle\NotificationBundle\Async\SendEmailMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SendEmailMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_notification.async.send_email_message_processor');

        $this->assertInstanceOf(SendEmailMessageProcessor::class, $service);
    }
}
