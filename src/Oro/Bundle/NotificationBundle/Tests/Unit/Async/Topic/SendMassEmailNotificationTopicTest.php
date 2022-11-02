<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\NotificationBundle\Async\Topic\SendMassEmailNotificationTopic;
use Oro\Component\MessageQueue\Topic\TopicInterface;

class SendMassEmailNotificationTopicTest extends SendEmailNotificationTopicTest
{
    protected function getTopic(): TopicInterface
    {
        return new SendMassEmailNotificationTopic();
    }
}
