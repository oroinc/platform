<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job\Topic;

use Oro\Component\MessageQueue\Job\Topic\RootJobStoppedTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class RootJobStoppedTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new RootJobStoppedTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'jobId' => 42,
                ],
                'expectedBody' => [
                    'jobId' => 42,
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
                'exceptionMessage' => '/The required option "jobId" is missing./',
            ],
        ];
    }
}
