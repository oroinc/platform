<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ImapBundle\Async\Topic\SyncEmailTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SyncEmailTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new SyncEmailTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'id' => 42,
                ],
                'expectedBody' => [
                    'id' => 42,
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'id' => '42',
                ],
                'expectedBody' => [
                    'id' => '42',
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
                'exceptionMessage' => '/The required option "id" is missing./',
            ],
        ];
    }
}
