<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Meta\TopicMeta;

class TopicMetaTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithNameOnly()
    {
        $topic = new TopicMeta('aName');

        static::assertEquals('aName', $topic->getName());
        static::assertEquals('', $topic->getDescription());
        static::assertEquals([], $topic->getSubscribers());
    }

    public function testCouldBeConstructedWithNameAndDescriptionOnly()
    {
        $topic = new TopicMeta('aName', 'aDescription');

        static::assertEquals('aName', $topic->getName());
        static::assertEquals('aDescription', $topic->getDescription());
        static::assertEquals([], $topic->getSubscribers());
    }

    public function testCouldBeConstructedWithNameAndDescriptionAndSubscribers()
    {
        $topic = new TopicMeta('aName', 'aDescription', ['aSubscriber']);

        static::assertEquals('aName', $topic->getName());
        static::assertEquals('aDescription', $topic->getDescription());
        static::assertEquals(['aSubscriber'], $topic->getSubscribers());
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $topic = new TopicMeta('theName', 'aDescription');

        static::assertSame('theName', $topic->getName());
    }

    public function testShouldAllowGetDescriptionSetInConstructor()
    {
        $topic = new TopicMeta('aName', 'theDescription');

        static::assertSame('theDescription', $topic->getDescription());
    }

    public function testShouldAllowGetSubscribersSetInConstructor()
    {
        $topic = new TopicMeta('aName', '', ['aSubscriber']);

        static::assertSame(['aSubscriber'], $topic->getSubscribers());
    }
}
