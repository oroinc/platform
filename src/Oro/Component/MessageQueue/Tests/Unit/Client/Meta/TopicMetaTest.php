<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\Meta\TopicMeta;

class TopicMetaTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $topic = new TopicMeta('aName');

        self::assertEquals('aName', $topic->getName());
    }

    public function testGetDefaultQueueNames(): void
    {
        $topic = new TopicMeta('aName');

        self::assertEquals([Config::DEFAULT_QUEUE_NAME], $topic->getQueueNames());
    }

    public function testGetQueueNames(): void
    {
        $topic = new TopicMeta('aName', ['sampleQueue']);

        self::assertEquals(['sampleQueue'], $topic->getQueueNames());
    }

    public function testGetMessageProcessorName(): void
    {
        $topic = new TopicMeta('aName', ['sampleQueue'], ['sampleQueue' => 'aSubscriber']);

        self::assertEquals('aSubscriber', $topic->getMessageProcessorName('sampleQueue'));
    }

    public function testGetMessageProcessorNameReturnsEmptyString(): void
    {
        $topic = new TopicMeta('aName', ['sampleQueue'], ['sampleQueue' => 'aSubscriber']);

        self::assertEquals('', $topic->getMessageProcessorName());
    }

    public function testGetMessageProcessorNameReturnsProcessorForDefaultQueue(): void
    {
        $topic = new TopicMeta('aName', [Config::DEFAULT_QUEUE_NAME], [Config::DEFAULT_QUEUE_NAME => 'aSubscriber']);

        self::assertEquals('aSubscriber', $topic->getMessageProcessorName());
    }
}
