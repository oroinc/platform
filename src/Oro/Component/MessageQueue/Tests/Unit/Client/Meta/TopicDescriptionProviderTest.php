<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Meta\TopicDescriptionProvider;
use Oro\Component\MessageQueue\Tests\Unit\Stub\TopicStub;
use Oro\Component\MessageQueue\Topic\TopicRegistry;
use PHPUnit\Framework\TestCase;

class TopicDescriptionProviderTest extends TestCase
{
    private TopicDescriptionProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $topicRegistry = $this->createMock(TopicRegistry::class);
        $topicRegistry->expects(self::any())
            ->method('has')
            ->willReturnMap([[TopicStub::getName(), true], ['sample_topic2', false]]);
        $topicRegistry->expects(self::any())
            ->method('get')
            ->with(TopicStub::getName())
            ->willReturn(new TopicStub());

        $this->provider = new TopicDescriptionProvider($topicRegistry);
    }

    public function testGetTopicDescriptionReturnsDescriptionFromTopic(): void
    {
        self::assertEquals(TopicStub::getDescription(), $this->provider->getTopicDescription(TopicStub::getName()));
    }

    public function testGetTopicDescriptionWhenAnotherCase(): void
    {
        self::assertEquals(
            TopicStub::getDescription(),
            $this->provider->getTopicDescription(strtoupper(TopicStub::getName()))
        );
    }

    public function testGetTopicDescriptionReturnsEmptyStringWhenNoTopic(): void
    {
        self::assertEquals('', $this->provider->getTopicDescription('sample_topic2'));
    }
}
