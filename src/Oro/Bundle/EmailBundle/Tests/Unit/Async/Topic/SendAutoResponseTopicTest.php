<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponseTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SendAutoResponseTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new SendAutoResponseTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    'jobId' => 42,
                    'id' => 142,
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'id' => 142,
                ],
            ],
            'options with alternative types' => [
                'body' => [
                    'jobId' => 42,
                    'id' => '142',
                ],
                'expectedBody' => [
                    'jobId' => 42,
                    'id' => '142',
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
                'exceptionMessage' => '/The required options "id", "jobId" are missing./',
            ],
        ];
    }
}
