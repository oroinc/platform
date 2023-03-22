<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Topic;

use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Oro\Component\MessageQueue\Topic\NullTopic;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Oro\Component\MessageQueue\Topic\TopicRegistry;
use Symfony\Contracts\Service\ServiceProviderInterface;

class TopicRegistryTest extends \PHPUnit\Framework\TestCase
{
    private ServiceProviderInterface|\PHPUnit\Framework\MockObject\MockObject $topicServiceProvider;

    private ServiceProviderInterface|\PHPUnit\Framework\MockObject\MockObject $jobAwareTopicServiceProvider;

    private TopicRegistry $registry;

    protected function setUp(): void
    {
        $this->topicServiceProvider = $this->createMock(ServiceProviderInterface::class);
        $this->jobAwareTopicServiceProvider = $this->createMock(ServiceProviderInterface::class);
        $this->registry = new TopicRegistry($this->topicServiceProvider, $this->jobAwareTopicServiceProvider);
    }

    public function testHas(): void
    {
        $topicName = 'sample_topic';
        $this->topicServiceProvider
            ->expects(self::once())
            ->method('has')
            ->with($topicName)
            ->willReturn(true);

        self::assertTrue($this->registry->has($topicName));
    }

    public function testGetJobAware(): void
    {
        $topicName = 'sample_topic';

        $this->jobAwareTopicServiceProvider
            ->expects(self::once())
            ->method('has')
            ->with($topicName)
            ->willReturn(true);

        $topic = $this->createMock(JobAwareTopicInterface::class);
        $this->jobAwareTopicServiceProvider
            ->expects(self::once())
            ->method('get')
            ->with($topicName)
            ->willReturn($topic);

        self::assertSame($topic, $this->registry->getJobAware($topicName));
    }

    public function testGetJobAwareWithUnknownTopic(): void
    {
        $topicName = 'sample_topic';

        $this->jobAwareTopicServiceProvider
            ->expects(self::once())
            ->method('has')
            ->with($topicName)
            ->willReturn(false);

        $this->jobAwareTopicServiceProvider
            ->expects(self::never())
            ->method('get');

        self::assertNull($this->registry->getJobAware($topicName));
    }

    public function testGetReturnsNullTopicWhenEmptyTopicName(): void
    {
        self::assertInstanceOf(NullTopic::class, $this->registry->get(''));
    }

    public function testGetReturnsNullTopicWhenNoTopic(): void
    {
        $topicName = 'missing_topic';
        $this->topicServiceProvider
            ->expects(self::once())
            ->method('has')
            ->with($topicName)
            ->willReturn(false);

        self::assertInstanceOf(NullTopic::class, $this->registry->get($topicName));
    }

    public function testGetReturnsTopic(): void
    {
        $topicName = 'sample_topic';

        $this->topicServiceProvider
            ->expects(self::once())
            ->method('has')
            ->with($topicName)
            ->willReturn(true);

        $topic = $this->createMock(TopicInterface::class);
        $this->topicServiceProvider
            ->expects(self::once())
            ->method('get')
            ->with($topicName)
            ->willReturn($topic);

        self::assertSame($topic, $this->registry->get($topicName));
    }

    public function testGetAllReturnsEmptyWhenNoTopics(): void
    {
        $this->topicServiceProvider
            ->expects(self::once())
            ->method('getProvidedServices')
            ->willReturn([]);

        $topics = $this->registry->getAll();
        self::assertIsIterable($topics);
        self::assertCount(0, $topics);
    }

    public function testGetAll(): void
    {
        $topicName1 = 'sample_topic_1';
        $topicName2 = 'sample_topic_2';

        $this->topicServiceProvider
            ->expects(self::once())
            ->method('getProvidedServices')
            ->willReturn([$topicName1 => TopicInterface::class, $topicName2 => TopicInterface::class]);

        $this->topicServiceProvider
            ->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive([$topicName1], [$topicName2])
            ->willReturn(true);

        $topic1 = $this->createMock(TopicInterface::class);
        $topic2 = $this->createMock(TopicInterface::class);
        $this->topicServiceProvider
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive([$topicName1], [$topicName2])
            ->willReturnOnConsecutiveCalls($topic1, $topic2);

        $topics = $this->registry->getAll();
        self::assertInstanceOf(\Traversable::class, $topics);
        self::assertEqualsCanonicalizing([$topicName1 => $topic1, $topicName2 => $topic2], iterator_to_array($topics));
    }
}
