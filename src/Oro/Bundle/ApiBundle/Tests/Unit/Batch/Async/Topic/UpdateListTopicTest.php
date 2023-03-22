<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async\Topic;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UpdateListTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new UpdateListTopic();
    }

    public function validBodyDataProvider(): array
    {
        $requiredOptionsSet = [
            'operationId' => 1,
            'entityClass' => \stdClass::class,
            'requestType' => [
                'bar',
                'baz',
            ],
            'version' => 'latest',
            'fileName' => 'file_name',
            'chunkSize' => 100,
            'includedDataChunkSize' => 100,
        ];
        $fullOptionsSet = array_merge(
            $requiredOptionsSet,
            [
                'splitterState' => [
                    'foo',
                ],
                'aggregateTime' => 1,
            ]
        );

        return [
            'only required options' => [
                'body' => $requiredOptionsSet,
                'expectedBody' => array_merge(
                    $requiredOptionsSet,
                    [
                        'aggregateTime' => 0,
                    ]
                ),
            ],
            'full set of options' => [
                'body' => $fullOptionsSet,
                'expectedBody' => $fullOptionsSet,
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "chunkSize", "entityClass", "fileName", "includedDataChunkSize",'
                    . ' "operationId", "requestType", "version" are missing./',
            ],
            'wrong operationId type' => [
                'body' => [
                    'operationId' => '1',
                    'entityClass' => \stdClass::class,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'version' => 'latest',
                    'fileName' => 'file_name',
                    'chunkSize' => 100,
                    'includedDataChunkSize' => 100,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "operationId" with value "1" is expected to be of type "int"/',
            ],
            'wrong entityClass type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => 1,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'version' => 'latest',
                    'fileName' => 'file_name',
                    'chunkSize' => 100,
                    'includedDataChunkSize' => 100,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "entityClass" with value 1 is expected to be of type "string"/',
            ],
            'wrong requestType type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => 'bar',
                    'version' => 'latest',
                    'fileName' => 'file_name',
                    'chunkSize' => 100,
                    'includedDataChunkSize' => 100,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "requestType" with value "bar" is expected '
                    . 'to be of type "string\[\]"/',
            ],
            'wrong version type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'version' => 1,
                    'fileName' => 'file_name',
                    'chunkSize' => 100,
                    'includedDataChunkSize' => 100,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "version" with value 1 is expected to be of type "string"/',
            ],
            'wrong fileName type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'version' => 'latest',
                    'fileName' => null,
                    'chunkSize' => 100,
                    'includedDataChunkSize' => 100,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "fileName" with value null is expected to be of type "string"/',
            ],
            'wrong chunkSize type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'version' => 'latest',
                    'fileName' => 'file_name',
                    'chunkSize' => '100',
                    'includedDataChunkSize' => 100,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "chunkSize" with value "100" is expected to be of type "int"/',
            ],
            'wrong includedDataChunkSize type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'version' => 'latest',
                    'fileName' => 'file_name',
                    'chunkSize' => 100,
                    'includedDataChunkSize' => '100',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "includedDataChunkSize" with value "100" '
                    . 'is expected to be of type "int"/',
            ],
            'wrong splitterState type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'version' => 'latest',
                    'fileName' => 'file_name',
                    'chunkSize' => 100,
                    'includedDataChunkSize' => 100,
                    'splitterState' => 'foo',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "splitterState" with value "foo" is expected to be of type "array"/',
            ],
            'wrong aggregateTime type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'version' => 'latest',
                    'fileName' => 'file_name',
                    'chunkSize' => 100,
                    'includedDataChunkSize' => 100,
                    'aggregateTime' => '1',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "aggregateTime" with value "1" is expected to be of type "int"/',
            ],
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro:batch_api:42',
            $this->getTopic()->createJobName(['operationId' => 42])
        );
    }
}
