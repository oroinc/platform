<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Registry;

use Oro\Bundle\SyncBundle\Registry\SubscribedTopicsRegistry;
use Ratchet\Wamp\Topic;

class SubscribedTopicsRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SubscribedTopicsRegistry
     */
    private $subscribedTopicsRegistry;

    protected function setUp()
    {
        $this->subscribedTopicsRegistry = new SubscribedTopicsRegistry();
    }

    public function testAddGetTopics()
    {
        $topicId1 = 'sampleTopic1';
        $topicId2 = 'sampleTopic2';
        $topic1 = $this->createTopic($topicId1);
        $topic2 = $this->createTopic($topicId2);
        $this->subscribedTopicsRegistry->addTopic($this->createTopic($topicId1));

        self::assertEquals([$topicId1 => $topic1], $this->subscribedTopicsRegistry->getTopics());

        $this->subscribedTopicsRegistry->addTopic($this->createTopic($topicId2));

        self::assertEquals([$topicId1 => $topic1, $topicId2 => $topic2], $this->subscribedTopicsRegistry->getTopics());
    }

    public function testHasTopic()
    {
        $topicId1 = 'sampleTopic1';
        $topic1 = $this->createTopic($topicId1);

        self::assertFalse($this->subscribedTopicsRegistry->hasTopic($topic1));

        $this->subscribedTopicsRegistry->addTopic($this->createTopic($topicId1));

        self::assertTrue($this->subscribedTopicsRegistry->hasTopic($topic1));
    }

    public function testRemoveTopic()
    {
        $topicId1 = 'sampleTopic1';
        $topic1 = $this->createTopic($topicId1);

        self::assertFalse($this->subscribedTopicsRegistry->removeTopic($topic1));

        $this->subscribedTopicsRegistry->addTopic($this->createTopic($topicId1));

        self::assertTrue($this->subscribedTopicsRegistry->removeTopic($topic1));
    }

    /**
     * @param string $topicId
     *
     * @return Topic
     */
    private function createTopic(string $topicId)
    {
        return new Topic($topicId);
    }
}
