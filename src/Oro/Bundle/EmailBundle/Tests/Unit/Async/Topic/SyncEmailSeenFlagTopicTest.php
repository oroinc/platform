<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\SyncEmailSeenFlagTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SyncEmailSeenFlagTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new SyncEmailSeenFlagTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'id' => 42,
                    'seen' => true,
                ],
                'expectedBody' => [
                    'id' => 42,
                    'seen' => true,
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'id' => '42',
                    'seen' => 1,
                ],
                'expectedBody' => [
                    'id' => '42',
                    'seen' => 1,
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "id", "seen" are missing./',
            ],
        ];
    }
}
