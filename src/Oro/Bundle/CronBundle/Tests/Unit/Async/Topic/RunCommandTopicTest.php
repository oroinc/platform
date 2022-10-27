<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\CronBundle\Async\Topic\RunCommandTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class RunCommandTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new RunCommandTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => ['command' => 'sample-command'],
                'expectedBody' => ['command' => 'sample-command', 'arguments' => []],
            ],
            'all options' => [
                'body' => ['command' => 'sample-command', 'arguments' => ['sample-argument'], 'jobId' => 42],
                'expectedBody' => ['command' => 'sample-command', 'arguments' => ['sample-argument'], 'jobId' => 42],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "command" is missing./',
            ],
            'with invalid arguments type' => [
                'body' => ['command' => 'sample-command', 'arguments' => 'sample-argument'],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "arguments" with value "sample-argument" is expected ' .
                    'to be of type "array", but is of type "string"./',
            ],
            'with invalid jobId type' => [
                'body' => ['command' => 'sample-command', 'jobId' => 'test'],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "jobId" with value "test" is expected to be of type "int", '
                    . 'but is of type "string"./',
            ],
        ];
    }
}
