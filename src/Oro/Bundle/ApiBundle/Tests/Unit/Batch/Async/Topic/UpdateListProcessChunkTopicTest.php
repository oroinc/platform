<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async\Topic;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListProcessChunkTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UpdateListProcessChunkTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new UpdateListProcessChunkTopic();
    }

    public function validBodyDataProvider(): array
    {
        $requiredOptionsSet = [
            'operationId' => 1,
            'entityClass' => \stdClass::class,
            'fileIndex' => 1,
            'fileName' => 'foo.bar',
            'firstRecordOffset' => 1,
            'jobId' => 1,
            'requestType' => [
                'bar',
                'baz',
            ],
            'sectionName' => 'sectionFoo',
            'version' => 'latest',
        ];
        $fullOptionsSet = array_merge(
            $requiredOptionsSet,
            [
                'extra_chunk' => true,
            ]
        );

        return [
            'only required options' => [
                'body' => $requiredOptionsSet,
                'expectedBody' => array_merge(
                    $requiredOptionsSet,
                    [
                        'extra_chunk' => false,
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
                    '/The required options "entityClass", "fileIndex", "fileName", "firstRecordOffset", "jobId", '
                        . '"operationId", "requestType", "sectionName", "version" are missing./',
            ],
            'wrong operationId type' => [
                'body' => [
                    'operationId' => '1',
                    'entityClass' => \stdClass::class,
                    'fileIndex' => 1,
                    'fileName' => 'foo.bar',
                    'firstRecordOffset' => 1,
                    'jobId' => 1,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'sectionName' => 'sectionFoo',
                    'version' => 'latest',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "operationId" with value "1" is expected to be of type "int"/',
            ],
            'wrong entityClass type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => 1,
                    'fileIndex' => 1,
                    'fileName' => 'foo.bar',
                    'firstRecordOffset' => 1,
                    'jobId' => 1,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'sectionName' => 'sectionFoo',
                    'version' => 'latest',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "entityClass" with value 1 is expected to be of type "string"/',
            ],
            'wrong fileIndex type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'fileIndex' => '1',
                    'fileName' => 'foo.bar',
                    'firstRecordOffset' => 1,
                    'jobId' => 1,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'sectionName' => 'sectionFoo',
                    'version' => 'latest',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "fileIndex" with value "1" is expected to be of type "int"/',
            ],
            'wrong fileName type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'fileIndex' => 1,
                    'fileName' => 1,
                    'firstRecordOffset' => 1,
                    'jobId' => 1,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'sectionName' => 'sectionFoo',
                    'version' => 'latest',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "fileName" with value 1 is expected to be of type "string"/',
            ],
            'wrong firstRecordOffset type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'fileIndex' => 1,
                    'fileName' => 'foo.bar',
                    'firstRecordOffset' => '1',
                    'jobId' => 1,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'sectionName' => 'sectionFoo',
                    'version' => 'latest',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "firstRecordOffset" with value "1" is expected to be of type "int"/',
            ],
            'wrong jobId type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'fileIndex' => 1,
                    'fileName' => 'foo.bar',
                    'firstRecordOffset' => 1,
                    'jobId' => '1',
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'sectionName' => 'sectionFoo',
                    'version' => 'latest',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "jobId" with value "1" is expected to be of type "int"/',
            ],
            'wrong requestType type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'fileIndex' => 1,
                    'fileName' => 'foo.bar',
                    'firstRecordOffset' => 1,
                    'jobId' => 1,
                    'requestType' => 'bar',
                    'sectionName' => 'sectionFoo',
                    'version' => 'latest',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "requestType" with value "bar" is expected '
                    . 'to be of type "string\[\]"/',
            ],
            'wrong sectionName type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'fileIndex' => 1,
                    'fileName' => 'foo.bar',
                    'firstRecordOffset' => 1,
                    'jobId' => 1,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'sectionName' => 1,
                    'version' => 'latest',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "sectionName" with value 1 is expected to be of type "string"/',
            ],
            'wrong version type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'fileIndex' => 1,
                    'fileName' => 'foo.bar',
                    'firstRecordOffset' => 1,
                    'jobId' => 1,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'sectionName' => 'sectionFoo',
                    'version' => 1.1,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "version" with value 1.1 is expected to be of type "string"/',
            ],
            'wrong extra_chunk type' => [
                'body' => [
                    'operationId' => 1,
                    'entityClass' => \stdClass::class,
                    'fileIndex' => 1,
                    'fileName' => 'foo.bar',
                    'firstRecordOffset' => 1,
                    'jobId' => 1,
                    'requestType' => [
                        'bar',
                        'baz',
                    ],
                    'sectionName' => 'sectionFoo',
                    'version' => 'latest',
                    'extra_chunk' => 1,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "extra_chunk" with value 1 is expected to be of type "bool"/',
            ],
        ];
    }
}
