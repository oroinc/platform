<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EntityConfigBundle\Async\Topic\AttributePreImportTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;

class AttributePreImportTopicTest extends AbstractTopicTestCase
{
    private const BATCH_SIZE = 5000;

    protected function getTopic(): TopicInterface
    {
        return new AttributePreImportTopic(self::BATCH_SIZE);
    }

    public function testGetName(): void
    {
        self::assertEquals(AttributePreImportTopic::NAME, $this->getTopic()::getName());
    }
}
