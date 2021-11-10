<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Meta\TopicDescriptionProvider;

class TopicDescriptionProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTopicDescription(): void
    {
        $provider = new TopicDescriptionProvider(['sample_name1' => 'sample_description1']);

        self::assertEquals('sample_description1', $provider->getTopicDescription('sample_name1'));
    }

    public function testGetTopicDescriptionWhenAnotherCase(): void
    {
        $provider = new TopicDescriptionProvider(['sample_name1' => 'sample_description1']);

        self::assertEquals('sample_description1', $provider->getTopicDescription('SAMPLE_NAME1'));
    }

    public function testGetTopicDescriptionReturnsEmptyStringWhenNoTopic(): void
    {
        $provider = new TopicDescriptionProvider(['sample_name1' => 'sample_description1']);

        self::assertEquals('', $provider->getTopicDescription(''));
    }
}
