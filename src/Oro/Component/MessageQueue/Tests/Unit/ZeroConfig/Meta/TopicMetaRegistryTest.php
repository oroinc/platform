<?php
namespace Oro\Component\MessageQueue\Tests\Unit\ZeroConfig\Meta;

use Oro\Component\MessageQueue\ZeroConfig\Meta\TopicMeta;
use Oro\Component\MessageQueue\ZeroConfig\Meta\TopicMetaRegistry;

class TopicMetaRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithTopics()
    {
        $topics = [
            'aTopicName' => [],
            'anotherTopicName' => []
        ];

        $registry = new TopicMetaRegistry($topics);

        $this->assertAttributeEquals($topics, 'topicsMeta', $registry);
    }

    public function testThrowIfThereIsNotMetaForRequestedTopicName()
    {
        $registry = new TopicMetaRegistry([]);

        $this->setExpectedException(
            \InvalidArgumentException::class,
            'The topic meta not found. Requested name `aName`'
        );
        $registry->getTopicMeta('aName');
    }

    public function testShouldAllowGetTopicByNameWithDefaultInfo()
    {
        $topics = [
            'theTopicName' => [],
        ];

        $registry = new TopicMetaRegistry($topics);

        $topic = $registry->getTopicMeta('theTopicName');
        $this->assertInstanceOf(TopicMeta::class, $topic);
        $this->assertSame('theTopicName', $topic->getName());
        $this->assertSame('', $topic->getDescription());
        $this->assertSame([], $topic->getSubscribers());
    }

    public function testShouldAllowGetTopicByNameWithCustomInfo()
    {
        $topics = [
            'theTopicName' => ['description' => 'theDescription', 'subscribers' => ['theSubscriber']],
        ];

        $registry = new TopicMetaRegistry($topics);

        $topic = $registry->getTopicMeta('theTopicName');
        $this->assertInstanceOf(TopicMeta::class, $topic);
        $this->assertSame('theTopicName', $topic->getName());
        $this->assertSame('theDescription', $topic->getDescription());
        $this->assertSame(['theSubscriber'], $topic->getSubscribers());
    }

    public function testShouldAllowGetAllTopics()
    {
        $topics = [
            'fooTopicName' => [],
            'barTopicName' => [],
        ];

        $registry = new TopicMetaRegistry($topics);

        $topics = $registry->getTopicsMeta();
        $this->assertInstanceOf(\Generator::class, $topics);

        $topics = iterator_to_array($topics);
        /** @var TopicMeta[] $topics */

        $this->assertContainsOnly(TopicMeta::class, $topics);
        $this->assertCount(2, $topics);

        $this->assertSame('fooTopicName', $topics[0]->getName());
        $this->assertSame('barTopicName', $topics[1]->getName());
    }
}
