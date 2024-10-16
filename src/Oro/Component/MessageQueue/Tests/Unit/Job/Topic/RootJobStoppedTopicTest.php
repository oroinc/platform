<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job\Topic;

use Oro\Component\MessageQueue\Job\Topic\RootJobStoppedTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class RootJobStoppedTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new RootJobStoppedTopic();
    }

    #[\Override]
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

    #[\Override]
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
