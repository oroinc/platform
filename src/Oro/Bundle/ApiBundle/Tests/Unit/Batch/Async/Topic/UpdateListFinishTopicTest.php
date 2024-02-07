<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async\Topic;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListFinishTopic;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UpdateListFinishTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new UpdateListFinishTopic();
    }

    public function validBodyDataProvider(): array
    {
        $fullOptionsSet = [
            'operationId' => 1,
            'entityClass' => 'TestEntity',
            'requestType' => [RequestType::BATCH],
            'version' => '1707118580',
            'fileName' => 'foo.bar',
        ];

        return [
            'full set of options' => [
                'body' => $fullOptionsSet,
                'expectedBody' => $fullOptionsSet,
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "entityClass", "fileName", "operationId", "requestType", '.
                    '"version" are missing./',
            ],
            'wrong operationId type' => [
                'body' => [
                    'operationId' => '1',
                    'fileName' => 'foo.bar',
                    'entityClass' => 'TestEntity',
                    'requestType' => [RequestType::BATCH],
                    'version' => '170711858',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "operationId" with value "1" is expected to be of type "int"/',
            ],
            'wrong entityClass type' => [
                'body' => [
                    'operationId' => 1,
                    'fileName' => 'foo.bar',
                    'entityClass' => 42,
                    'requestType' => [RequestType::BATCH],
                    'version' => '170711858',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "entityClass" with value 42 is expected to be of type "string",'.
                    ' but is of type "int"./',
            ],
            'entityClass is null' => [
                'body' => [
                    'operationId' => 1,
                    'fileName' => 'foo.bar',
                    'entityClass' => null,
                    'requestType' => [RequestType::BATCH],
                    'version' => '170711858',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "entityClass" with value null is expected to be of type "string",'.
                    ' but is of type "null"./',
            ],
            'wrong requestType type' => [
                'body' => [
                    'operationId' => 1,
                    'fileName' => 'foo.bar',
                    'entityClass' => 'TestEntity',
                    'requestType' => null,
                    'version' => '170711858',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "requestType" with value null is expected to be of type'.
                    ' "string\[\]", but is of type "null"./',
            ],
            'wrong version type' => [
                'body' => [
                    'operationId' => 1,
                    'fileName' => 'foo.bar',
                    'entityClass' => 'TestEntity',
                    'requestType' => [RequestType::BATCH],
                    'version' => 170711858,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "version" with value 170711858 is expected to be of type'.
                    ' "string", but is of type "int"./',
            ],
            'wrong fileName type' => [
                'body' => [
                    'operationId' => 1,
                    'fileName' => 1,
                    'entityClass' => 'TestEntity',
                    'requestType' => [RequestType::BATCH],
                    'version' => '170711858',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "fileName" with value 1 is expected to be of type "string"/',
            ],
        ];
    }
}
