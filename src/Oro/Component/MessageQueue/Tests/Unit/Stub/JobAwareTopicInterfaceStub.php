<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Stub;

use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Oro\Component\MessageQueue\Topic\TopicInterface;

interface JobAwareTopicInterfaceStub extends TopicInterface, JobAwareTopicInterface
{
}
