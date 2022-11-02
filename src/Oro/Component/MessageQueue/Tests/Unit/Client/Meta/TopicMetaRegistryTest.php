<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\Meta\TopicMeta;
use Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry;

class TopicMetaRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldAllowGetTopicByNameWithDefaultInfo(): void
    {
        $registry = new TopicMetaRegistry([], []);

        $topic = $registry->getTopicMeta('sample_topic');

        self::assertSame('sample_topic', $topic->getName());
        self::assertEmpty($topic->getMessageProcessorName('sample_queue'));
        self::assertEquals([Config::DEFAULT_QUEUE_NAME], $topic->getQueueNames());
    }

    public function testShouldAllowGetTopicByNameWithCustomInfo(): void
    {
        $queuesByTopic = ['sample_topic' => ['sample_queue']];
        $messageProcessorsByTopicAndQueue = ['sample_topic' => ['sample_queue' => 'message_processor']];

        $registry = new TopicMetaRegistry($queuesByTopic, $messageProcessorsByTopicAndQueue);

        $topic = $registry->getTopicMeta('sample_topic');

        self::assertSame('sample_topic', $topic->getName());
        self::assertSame('message_processor', $topic->getMessageProcessorName('sample_queue'));
        self::assertSame(['sample_queue'], $topic->getQueueNames());
    }

    public function testShouldAllowGetAllTopics(): void
    {
        $queuesByTopic = ['sample_topic1' => ['sample_queue1'], 'sample_topic2' => ['sample_queue2']];
        $messageProcessorsByTopicAndQueue = [
            'sample_topic1' => ['sample_queue1' => 'message_processor1'],
            'sample_topic2' => ['sample_queue2' => 'message_processor2'],
        ];

        $registry = new TopicMetaRegistry($queuesByTopic, $messageProcessorsByTopicAndQueue);

        $topics = $registry->getTopicsMeta();
        $topics = iterator_to_array($topics);

        self::assertContainsOnly(TopicMeta::class, $topics);
        self::assertCount(2, $topics);

        self::assertSame('sample_topic1', $topics[0]->getName());
        self::assertSame('sample_topic2', $topics[1]->getName());
    }
}
