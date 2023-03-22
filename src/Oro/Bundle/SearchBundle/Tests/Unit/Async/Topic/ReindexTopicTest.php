<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\SearchBundle\Async\Topic\ReindexTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class ReindexTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ReindexTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'expectedBody' => [],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'invalid option' => [
                'body' => ['invalid_key' => 'invalid_value'],
                'exceptionClass' => UndefinedOptionsException::class,
                'exceptionMessage' => '/The option "invalid_key" does not exist./',
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro.search.reindex',
            $this->getTopic()->createJobName([])
        );
    }
}
