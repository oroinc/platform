<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional\Command;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\NotificationBundle\Async\Topic\SendMassEmailNotificationTopic;
use Oro\Bundle\NotificationBundle\Command\MassNotificationCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MassNotificationCommandTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    public function testCommand()
    {
        $this->initClient([], self::generateBasicAuthHeader());

        self::clearMessageCollector();
        self::assertMessagesCount(SendMassEmailNotificationTopic::getName(), 0);

        $result = self::runCommand(
            MassNotificationCommand::getDefaultName(),
            [
                '--subject' => 'sbj',
                '--message' => 'msg',
                '--sender_name' => 'bob',
                '--sender_email' => 'sender@example.com',
            ]
        );
        self::assertStringContainsString('1 notifications have been added to the queue', $result);

        self::assertMessagesCount(SendMassEmailNotificationTopic::getName(), 1);
        self::clearMessageCollector();
    }
}
