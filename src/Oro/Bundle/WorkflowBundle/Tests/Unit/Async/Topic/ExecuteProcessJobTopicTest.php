<?php

declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\WorkflowBundle\Async\Topic\ExecuteProcessJobTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ExecuteProcessJobTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ExecuteProcessJobTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            ['body' => ['process_job_id' => 42], 'expectedBody' => ['process_job_id' => 42]],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "process_job_id" is missing./',
            ],
            'process_job_id has invalid type' => [
                'body' => ['process_job_id' => new \stdClass()],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "process_job_id" with value stdClass is expected '
                    . 'to be of type "int"/',
            ],
        ];
    }
}
