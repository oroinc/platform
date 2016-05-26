<?php
namespace Oro\Component\MessageQueue\Tests\Unit\ZeroConfig;

use Oro\Component\MessageQueue\ZeroConfig\Topic;
use Oro\Component\MessageQueue\ZeroConfig\TopicRegistry;

class TopicRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithTopics()
    {
        $topics = [
            'aTopicName' => [],
            'anotherTopicName' => []
        ];

        $registry = new TopicRegistry($topics);

        $this->assertAttributeEquals($topics, 'topics', $registry);
    }

    public function testShouldAllowGetTopicByNameWithDefaultInfo()
    {
        $topics = [
            'theTopicName' => [],
        ];

        $registry = new TopicRegistry($topics);

        $topic = $registry->getTopic('theTopicName');
        $this->assertInstanceOf(Topic::class, $topic);
        $this->assertSame('theTopicName', $topic->getName());
        $this->assertSame('', $topic->getDescription());
    }

    public function testShouldAllowGetTopicByNameWithCustomInfo()
    {
        $topics = [
            'theTopicName' => ['description' => 'theDescription'],
        ];

        $registry = new TopicRegistry($topics);

        $topic = $registry->getTopic('theTopicName');
        $this->assertInstanceOf(Topic::class, $topic);
        $this->assertSame('theTopicName', $topic->getName());
        $this->assertSame('theDescription', $topic->getDescription());
    }

    public function testShouldAllowGetAllTopics()
    {
        $topics = [
            'fooTopicName' => [],
            'barTopicName' => [],
        ];

        $registry = new TopicRegistry($topics);

        $topics = $registry->getTopics();
        $this->assertInstanceOf(\Generator::class, $topics);

        $topics = iterator_to_array($topics);
        /** @var Topic[] $topics */

        $this->assertContainsOnly(Topic::class, $topics);
        $this->assertCount(2, $topics);

        $this->assertSame('fooTopicName', $topics[0]->getName());
        $this->assertSame('barTopicName', $topics[1]->getName());
    }
}
