<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional\Async;

use Oro\Bundle\NotificationBundle\Async\EmailSendingMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailSendingMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_notification.async.processor.email_sending');

        $this->assertInstanceOf(EmailSendingMessageProcessor::class, $service);
    }
}
