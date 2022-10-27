<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\CronBundle\Async\Topic\RunCommandDelayedTopic;
use Oro\Component\MessageQueue\Topic\TopicInterface;

class RunCommandDelayedTopicTest extends RunCommandTopicTest
{
    protected function getTopic(): TopicInterface
    {
        return new RunCommandDelayedTopic();
    }
}
